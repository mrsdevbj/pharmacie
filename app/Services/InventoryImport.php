<?php
function normalizeText($value): string
{
    return trim(str_replace(["\r", "\n"], ' ', (string) $value));
}

function parseCsvRows(string $filePath): array
{
    $handle = fopen($filePath, 'r');
    if ($handle === false) return [];
    $rows = [];
    while (($data = fgetcsv($handle, 10000, ';', '"', '')) !== false) {
        $rows[] = array_map('normalizeText', $data);
    }
    fclose($handle);
    return $rows;
}

function parseCsvRowsWithComma(string $filePath): array
{
    $handle = fopen($filePath, 'r');
    if ($handle === false) return [];
    $rows = [];
    while (($data = fgetcsv($handle, 10000, ',', '"', '')) !== false) {
        $rows[] = array_map('normalizeText', $data);
    }
    fclose($handle);
    return $rows;
}

function parseXlsxRows(string $filePath): array
{
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) return [];

    $sharedStrings = [];
    $sharedStringsXml = safeZipEntry($zip, 'xl/sharedStrings.xml');
    if ($sharedStringsXml !== false) {
        $xml = simplexml_load_string($sharedStringsXml, SimpleXMLElement::class, LIBXML_NONET);
        if ($xml !== false) {
            foreach ($xml->si as $si) {
                $text = '';
                foreach ($si->t as $t) $text .= (string) $t;
                $sharedStrings[] = $text;
            }
        }
    }

    $rows = [];
    $worksheetXml = safeZipEntry($zip, 'xl/worksheets/sheet1.xml');
    if ($worksheetXml !== false) {
        $xml = simplexml_load_string($worksheetXml, SimpleXMLElement::class, LIBXML_NONET);
        if ($xml !== false) {
            foreach ($xml->sheetData->row as $row) {
                $values = [];
                foreach ($row->c as $cell) {
                    $type = (string) $cell['t'];
                    $value = '';
                    if ($type === 'inlineStr') {
                        $value = normalizeText((string) $cell->is->t);
                    } elseif ($type === 's') {
                        $value = $sharedStrings[(int) $cell->v] ?? '';
                    } elseif ($type === 'b') {
                        $value = ((string) $cell->v === '1') ? '1' : '0';
                    } else {
                        $value = normalizeText((string) $cell->v);
                    }
                    $col = preg_replace('/[0-9]+$/', '', (string)$cell['r']);
                    if ($col === 'A') $values[0] = $value;
                    if ($col === 'B') $values[1] = $value;
                    if ($col === 'C') $values[2] = $value;
                }
                if (!empty($values)) $rows[] = [$values[0] ?? '', $values[1] ?? '', $values[2] ?? ''];
            }
        }
    }
    $zip->close();
    return $rows;
}

function extractMedicationData(array $row): ?array
{
    if (count($row) < 2) return null;
    $nom = preg_replace('/^\xEF\xBB\xBF/', '', normalizeText($row[0]));
    $quantiteText = normalizeText($row[1] ?? '');

    if ($nom === '') return null;

    if (!validQuantity($quantiteText, 0) || strlen($nom) > 190) return null;
    $quantite = (int) $quantiteText;

    return [
        'nom' => $nom,
        'quantite' => max(0, $quantite),
        'is_actif' => max(0, $quantite) > 0 ? 1 : 0,
    ];
}


function inventoryPreview(array $rows,bool $header): array {
 if($header)array_shift($rows);if(count($rows)>5000)throw new DomainException('Maximum 5 000 lignes par fichier.');$out=[];$seen=[];
 foreach($rows as $i=>$row){if(!array_filter($row,fn($v)=>trim((string)$v)!==''))continue;$data=extractMedicationData($row);$price=trim($row[2]??'');$parsed=$price===''?null:parsePrice($price);$error='';$name=$data['nom']??normalizeText($row[0]??'');$key=function_exists('mb_strtolower')?mb_strtolower($name,'UTF-8'):strtolower($name);if(!$data||count($row)>3||($price!==''&&$parsed===null)||!preg_match('//u',$name))$error='Valeurs invalides';elseif(isset($seen[$key]))$error='Doublon dans le fichier';$seen[$key]=true;$out[]=['line'=>$i+1+(int)$header,'nom'=>$name,'quantite'=>$data['quantite']??null,'prix'=>$parsed,'error'=>$error];}
 return $out;
}
