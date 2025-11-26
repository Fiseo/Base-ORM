$path = "C:\wamp64\www\Base-ORM-Framework\file.sql"

$data = @{}
$currentTable = $null

foreach ($line in Get-Content $path) {
    if ($line.Trim() -eq "") { continue } #inore chaque ligne vide

    if ($line -match "^\s*CREATE\s+TABLE\s+`?(\w+)`?") {
            $currentTable = $matches[1]
            $data[$currentTable] = @{
                fields = @()
                link   = @{}
            }
            continue
    }

    # ignorer les lignes non pertinentes
    if ($line -match "^\s*(DROP|CREATE|USE|PRIMARY|\))") {
        continue
    }

    # FOREIGN KEY (col) REFERENCES otherTable
    if ($line -match "^\s*FOREIGN\s+KEY\s*\((\w+)\)\s+REFERENCES\s+`?(\w+)`?") {
        $column = $matches[1]
        $refTable = $matches[2]

        $data[$currentTable]["link"][$refTable] = $column
        $data[$refTable]["link"][$currentTable] = $null
        continue
    }

    if ($line -match "^\s*(\w+)") {
        $field = $matches[1]
        $data[$currentTable]["fields"] += $field
        continue
    }
}

$outputDir = "Requete"

# Crée le dossier si nécessaire
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

foreach ($table in $data.Keys) {

    $className = ($table.Substring(0,1).ToUpper() + $table.Substring(1) + "Repository")
    $entityName = $table.Substring(0,1).ToUpper() + $table.Substring(1)

    $filePath = Join-Path $outputDir "$className.php"

    # Début du fichier
    $php = "<?php`n`n"
    $php += "namespace Requete;`n`n"
    $php += "class $className extends Core\EntityRepository`n"
    $php += "{`n"

    # Fields
    $php += "    protected static array `$fields = [`n"
    foreach ($field in $data[$table].fields) {
        # Ajoute chaque champ
        $php += "        '$field',`n"
    }
    $php += "    ];`n`n"

    # Entity
    $php += "    protected static string `$entity = '$entityName';`n`n"

    # Entity linked
    $php += "    protected static array `$entitylinked = [`n"
    foreach ($refTable in $data[$table].link.Keys) {
        $column = $data[$table].link[$refTable]
        if ($column) {
            $php += "        '$refTable' => '$column',`n"
        } else {
            $php += "        '$refTable' => null,`n"
        }
    }
    $php += "    ];`n"

    # Fin du fichier
    $php += "}`n"

    # Écriture fichier
    Set-Content -Path $filePath -Value $php -Encoding UTF8
}

Write-Host "Classes PHP générées dans : $outputDir"
