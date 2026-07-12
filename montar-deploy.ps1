#Requires -Version 5.1
<#
  montar-deploy.ps1
  Monta a branch deploy com os artefatos de backend + frontend/dist.
  Execute a partir da raiz do repositório:
    cd "C:\Users\orbis_om1t1ks\Documents\mapa-psique 2"
    .\montar-deploy.ps1
#>

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$Root     = $PSScriptRoot
$Worktree = Join-Path $Root ".deploy-worktree"

Write-Host "`n==> Verificando estado do repositório..." -ForegroundColor Cyan
Push-Location $Root

$branch = git rev-parse --abbrev-ref HEAD
if ($branch -ne 'main') {
    Write-Error "Execute este script a partir da branch main (atual: $branch)."
    exit 1
}

if (Test-Path $Worktree) {
    Write-Host "    Removendo worktree anterior..."
    git worktree remove --force $Worktree 2>$null
    Remove-Item -Recurse -Force $Worktree -ErrorAction SilentlyContinue
}

Write-Host "`n==> Criando worktree da branch deploy..." -ForegroundColor Cyan
git fetch origin deploy
git worktree add $Worktree deploy

# ─── Helper ──────────────────────────────────────────────────────────────────
function Copy-ToWorktree {
    param([string]$SrcRelative, [string]$DstRelative)
    $src = Join-Path $Root $SrcRelative
    $dst = Join-Path $Worktree $DstRelative
    New-Item -ItemType Directory -Force -Path (Split-Path $dst) | Out-Null
    Copy-Item $src $dst -Force
    Write-Host "    + $DstRelative"
}

# ─── BACKEND: Database/Repositories ──────────────────────────────────────────
Write-Host "`n==> Copiando Repositories..." -ForegroundColor Cyan
Copy-ToWorktree `
    "backend\src\Database\Repositories\AiAnalysisRepository.php" `
    "api/_app/src/Database/Repositories/AiAnalysisRepository.php"
Copy-ToWorktree `
    "backend\src\Database\Repositories\MapRepository.php" `
    "api/_app/src/Database/Repositories/MapRepository.php"

# ─── BACKEND: bin/worker.php ─────────────────────────────────────────────────
Write-Host "`n==> Copiando bin/worker.php..." -ForegroundColor Cyan
Copy-ToWorktree `
    "backend\bin\worker.php" `
    "api/_app/bin/worker.php"

# ─── BACKEND: Http ───────────────────────────────────────────────────────────
Write-Host "`n==> Copiando Http..." -ForegroundColor Cyan
Copy-ToWorktree `
    "backend\src\Http\BackgroundJobResponse.php" `
    "api/_app/src/Http/BackgroundJobResponse.php"

# ─── BACKEND: Modules/AiAnalysis ─────────────────────────────────────────────
Write-Host "`n==> Copiando Modules/AiAnalysis..." -ForegroundColor Cyan
$aiFiles = @(
    'AiController.php',
    'AiPromptBuilder.php',
    'AiService.php',
    'AnthropicClient.php',
    'CanvasGeneratorController.php',
    'OpenAiClient.php'
)
foreach ($f in $aiFiles) {
    Copy-ToWorktree `
        "backend\src\Modules\AiAnalysis\$f" `
        "api/_app/src/Modules/AiAnalysis/$f"
}

# ─── BACKEND: Modules/Maps ───────────────────────────────────────────────────
Write-Host "`n==> Copiando Modules/Maps novos..." -ForegroundColor Cyan
Copy-ToWorktree `
    "backend\src\Modules\Maps\MapImageController.php" `
    "api/_app/src/Modules/Maps/MapImageController.php"
Copy-ToWorktree `
    "backend\src\Modules\Maps\MapImageService.php" `
    "api/_app/src/Modules/Maps/MapImageService.php"
Copy-ToWorktree `
    "backend\src\Modules\Maps\MapService.php" `
    "api/_app/src/Modules/Maps/MapService.php"

# ─── BACKEND: Modules/Patients ───────────────────────────────────────────────
Write-Host "`n==> Copiando Modules/Patients novos..." -ForegroundColor Cyan
Copy-ToWorktree `
    "backend\src\Modules\Patients\PatientMapController.php" `
    "api/_app/src/Modules/Patients/PatientMapController.php"

# ─── BACKEND: index.php (injeção cirúrgica) ──────────────────────────────────
Write-Host "`n==> Atualizando api/_app/public/index.php..." -ForegroundColor Cyan

$indexPath = Join-Path $Worktree "api/_app/public/index.php"
$content   = Get-Content $indexPath -Raw -Encoding UTF8

# --- use statements ---
$useInserts = @{
    'use App\Modules\AiAnalysis\AiController;'              = 'use App\Modules\Auth\AuthController;'
    'use App\Modules\AiAnalysis\CanvasGeneratorController;' = 'use App\Modules\Auth\AuthController;'
    'use App\Modules\Maps\MapImageController;'              = 'use App\Modules\Maps\MapController;'
    'use App\Modules\Patients\PatientMapController;'        = 'use App\Modules\Patients\PatientController;'
}
foreach ($useStmt in $useInserts.Keys) {
    if ($content -notmatch [regex]::Escape($useStmt)) {
        $anchor  = $useInserts[$useStmt]
        $content = $content.Replace($anchor, "$useStmt`r`n$anchor")
        Write-Host "    + use $($useStmt -replace '^use ',''  -replace ';$','')"
    } else {
        Write-Host "    ~ já presente: $($useStmt -replace '^use ','' -replace ';$','')"
    }
}

# --- rotas ---
$routeBlocks = [ordered]@{
    # Criação de mapa a partir do paciente
    "POST /api/patients/{id}/create-map" = @{
        Check  = "patients/{id}/create-map"
        Before = '$router->post(''/api/patients/{id}/restore''' -replace "''","'"
        Insert = (
            '$router->post(''/api/patients/{id}/create-map'', [PatientMapController::class, ''createMap'']);' + "`r`n"
        ) -replace "''","'"
    }
    # Imagem do mapa (upload + serve)
    "POST /api/maps/{id}/image" = @{
        Check  = "api/maps/{id}/image"
        Before = '$router->get(''/api/maps/{id}/analysis/image''' -replace "''","'"
        Insert = (
            '$router->post(''/api/maps/{id}/image'', [MapImageController::class, ''upload'']);' + "`r`n" +
            '$router->get(''/api/maps/{id}/image'', [MapImageController::class, ''show'']);' + "`r`n" +
            '$router->post(''/api/maps/{id}/generate-canvas'', [CanvasGeneratorController::class, ''generate'']);' + "`r`n"
        ) -replace "''","'"
    }
    # Análise IA (profissional)
    "POST /api/maps/{id}/analysis" = @{
        Check  = "api/maps/{id}/analysis"
        Before = '$router->get(''/api/maps/{id}''' -replace "''","'"
        Insert = (
            '$router->get(''/api/maps/{id}/analysis/image'', [AiController::class, ''image'']);' + "`r`n" +
            '$router->get(''/api/maps/{id}/analysis'', [AiController::class, ''show'']);' + "`r`n" +
            '$router->post(''/api/maps/{id}/analysis'', [AiController::class, ''generate'']);' + "`r`n"
        ) -replace "''","'"
    }
}

foreach ($label in $routeBlocks.Keys) {
    $block  = $routeBlocks[$label]
    $check  = $block.Check
    $before = $block.Before
    $insert = $block.Insert

    if ($content -notmatch [regex]::Escape($check)) {
        # Anchor: "$router->get('/api/maps/{id}'," — add BEFORE it
        $anchor  = '$router->get(''/api/maps/{id}'',' -replace "''","'"
        $content = $content.Replace($before + ",", $insert + $before + ",")
        if ($content -notmatch [regex]::Escape($check)) {
            # Fallback: insert before the catch-all map route
            $content = $content.Replace($anchor, $insert + $anchor)
        }
        Write-Host "    + rotas $label"
    } else {
        Write-Host "    ~ rotas já presentes: $check"
    }
}

[System.IO.File]::WriteAllText($indexPath, $content, [System.Text.UTF8Encoding]::new($false))

# ─── BACKEND: storage dirs ───────────────────────────────────────────────────
Write-Host "`n==> Criando storage dirs..." -ForegroundColor Cyan

foreach ($subdir in @("uploads/ai", "uploads/maps")) {
    $dir  = Join-Path $Worktree "api/_app/storage/$subdir"
    $keep = Join-Path $Root "backend/storage/$subdir/.gitkeep"
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    if (Test-Path $keep) {
        Copy-Item $keep "$dir\.gitkeep" -Force
        Write-Host "    + $subdir/.gitkeep copiado"
    } else {
        New-Item -ItemType File -Force -Path "$dir\.gitkeep" | Out-Null
        Write-Host "    + $subdir/.gitkeep criado"
    }
}

# ─── FRONTEND: substituir artefatos estáticos ────────────────────────────────
Write-Host "`n==> Atualizando artefatos do frontend..." -ForegroundColor Cyan

$distDir   = Join-Path $Root "frontend\dist"
$assetsDir = Join-Path $Worktree "assets"

if (-not (Test-Path $distDir)) {
    Write-Error "frontend/dist não encontrado. Execute 'npm run build' primeiro."
    exit 1
}

if (Test-Path $assetsDir) {
    Remove-Item "$assetsDir\*" -Force -Recurse
    Write-Host "    - assets antigos removidos"
}
New-Item -ItemType Directory -Force -Path $assetsDir | Out-Null
Copy-Item "$distDir\assets\*" $assetsDir -Force -Recurse
Write-Host "    + assets novos copiados"

Copy-Item "$distDir\index.html" (Join-Path $Worktree "index.html") -Force
Write-Host "    + index.html atualizado"

# ─── GIT: stage, commit, push ────────────────────────────────────────────────
Write-Host "`n==> Fazendo commit na branch deploy..." -ForegroundColor Cyan
Push-Location $Worktree

git add `
    "api/_app/bin/worker.php" `
    "api/_app/src/Database/Repositories/" `
    "api/_app/src/Modules/AiAnalysis/" `
    "api/_app/src/Modules/Maps/MapImageController.php" `
    "api/_app/src/Modules/Maps/MapImageService.php" `
    "api/_app/src/Modules/Maps/MapService.php" `
    "api/_app/src/Modules/Patients/PatientMapController.php" `
    "api/_app/public/index.php" `
    "api/_app/storage/uploads/ai/.gitkeep" `
    "api/_app/storage/uploads/maps/.gitkeep" `
    "assets/" `
    "index.html"

git status --short

$changes = git status --porcelain
if ($changes) {
    git commit -m "feat: upload mapa no paciente + canvas via IA + fallback guiado"
    Write-Host "`n==> Enviando para origin/deploy..." -ForegroundColor Cyan
    git push origin deploy
} else {
    Write-Host "    Nenhuma alteração — branch deploy já está atualizada."
}

Pop-Location

# ─── Limpar worktree ─────────────────────────────────────────────────────────
Write-Host "`n==> Limpando worktree temporário..." -ForegroundColor Cyan
Pop-Location
Push-Location $Root
git worktree remove --force $Worktree
Remove-Item -Recurse -Force $Worktree -ErrorAction SilentlyContinue

Write-Host "`n✓ Deploy montado e publicado com sucesso!" -ForegroundColor Green
Write-Host "  GitHub Actions fará o git pull no servidor automaticamente.`n"
