<#
.SYNOPSIS
    Mencadangkan Lentera Desa: basis data MySQL dan berkas (templat serta PDF surat).

.DESCRIPTION
    Menghasilkan dua berkas di folder "cadangan" pada akar proyek:
      lentera_desa_<waktu>.sql   hasil ekspor basis data (mysqldump)
      berkas_<waktu>.zip         salinan storage\app\templat dan storage\app\surat
    Cadangan lama dihapus otomatis; yang disimpan hanya sejumlah terbaru (bawaan 14).
    Folder "cadangan" masuk .gitignore karena berisi data warga.

.PARAMETER Simpan
    Jumlah pasangan cadangan terbaru yang dipertahankan.

.PARAMETER Tujuan
    Folder tujuan cadangan. Bawaannya "cadangan" di akar proyek; arahkan ke flashdisk
    atau komputer lain agar cadangan tidak hanya berada di komputer server.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File scripts\cadangan.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File scripts\cadangan.ps1 -Tujuan E:\cadangan-desa -Simpan 30

.NOTES
    Kredensial dibaca dari .env (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD).
    Jalur mysqldump dapat diatur lewat MYSQLDUMP_PATH di .env; bila kosong dipakai
    C:\xampp\mysql\bin\mysqldump.exe, lalu mysqldump di PATH.
#>
[CmdletBinding()]
param(
    [int]$Simpan = 14,
    [string]$Tujuan
)

$ErrorActionPreference = 'Stop'
$akar = Split-Path -Parent $PSScriptRoot
if (-not $Tujuan) { $Tujuan = Join-Path $akar 'cadangan' }

function Baca-Env([string]$jalur) {
    $nilai = @{}
    foreach ($baris in Get-Content -LiteralPath $jalur -Encoding UTF8) {
        $baris = $baris.TrimStart([char]0xFEFF).Trim()
        if ($baris -eq '' -or $baris.StartsWith('#') -or -not $baris.Contains('=')) { continue }
        $kunci, $isi = $baris.Split('=', 2)
        $isi = $isi.Trim()
        if (($isi.StartsWith('"') -and $isi.EndsWith('"')) -or ($isi.StartsWith("'") -and $isi.EndsWith("'"))) {
            $isi = $isi.Substring(1, $isi.Length - 2)
        }
        $nilai[$kunci.Trim()] = $isi
    }
    return $nilai
}

$envBerkas = Join-Path $akar '.env'
if (-not (Test-Path -LiteralPath $envBerkas)) { throw "Berkas .env tidak ditemukan di $akar" }
$env = Baca-Env $envBerkas

$dbHost = if ($env['DB_HOST']) { $env['DB_HOST'] } else { '127.0.0.1' }
$dbPort = if ($env['DB_PORT']) { $env['DB_PORT'] } else { '3306' }
$dbNama = $env['DB_DATABASE']
$dbUser = if ($env['DB_USERNAME']) { $env['DB_USERNAME'] } else { 'root' }
$dbSandi = $env['DB_PASSWORD']
if (-not $dbNama) { throw 'DB_DATABASE belum diisi di .env' }

# Cari mysqldump
$mysqldump = $null
foreach ($calon in @($env['MYSQLDUMP_PATH'], 'C:\xampp\mysql\bin\mysqldump.exe')) {
    if ($calon -and (Test-Path -LiteralPath $calon)) { $mysqldump = $calon; break }
}
if (-not $mysqldump) {
    $dariPath = Get-Command mysqldump -ErrorAction SilentlyContinue
    if ($dariPath) { $mysqldump = $dariPath.Source }
}
if (-not $mysqldump) { throw 'mysqldump tidak ditemukan. Isi MYSQLDUMP_PATH di .env.' }

New-Item -ItemType Directory -Force -Path $Tujuan | Out-Null
$waktu = Get-Date -Format 'yyyyMMdd_HHmmss'
$berkasSql = Join-Path $Tujuan "lentera_desa_$waktu.sql"
$berkasZip = Join-Path $Tujuan "berkas_$waktu.zip"

# 1) Basis data. Kata sandi lewat berkas opsi sementara agar tidak muncul di daftar proses.
$opsi = Join-Path ([IO.Path]::GetTempPath()) ("lentera-" + [guid]::NewGuid().ToString('N') + '.cnf')
try {
    $isiOpsi = "[client]`nhost=$dbHost`nport=$dbPort`nuser=$dbUser`n"
    if ($dbSandi) { $isiOpsi += "password=`"$dbSandi`"`n" }
    [IO.File]::WriteAllText($opsi, $isiOpsi, (New-Object Text.UTF8Encoding($false)))

    Write-Host "Mengekspor basis data '$dbNama' ..."
    & $mysqldump "--defaults-extra-file=$opsi" --single-transaction --routines --default-character-set=utf8mb4 --result-file="$berkasSql" $dbNama
    if ($LASTEXITCODE -ne 0) { throw "mysqldump gagal (kode $LASTEXITCODE). Pastikan MySQL menyala dan kredensial di .env benar." }
}
finally {
    if (Test-Path -LiteralPath $opsi) { Remove-Item -LiteralPath $opsi -Force }
}

if (-not (Test-Path -LiteralPath $berkasSql) -or (Get-Item -LiteralPath $berkasSql).Length -lt 100) {
    throw 'Hasil ekspor basis data kosong; cadangan dibatalkan.'
}

# 2) Berkas (templat dan PDF surat)
$sumber = @('storage\app\templat', 'storage\app\surat') |
    ForEach-Object { Join-Path $akar $_ } |
    Where-Object { Test-Path -LiteralPath $_ }
Write-Host 'Mengarsipkan templat dan PDF surat ...'
Compress-Archive -Path $sumber -DestinationPath $berkasZip -Force

# 3) Hapus cadangan lama (hanya berkas ber-pola di folder tujuan)
foreach ($pola in @('lentera_desa_*.sql', 'berkas_*.zip')) {
    Get-ChildItem -LiteralPath $Tujuan -Filter $pola -File |
        Sort-Object LastWriteTime -Descending |
        Select-Object -Skip $Simpan |
        Remove-Item -Force
}

Write-Host ''
Write-Host 'Cadangan selesai:'
Write-Host ('  {0}  ({1:N0} KB)' -f $berkasSql, ((Get-Item -LiteralPath $berkasSql).Length / 1KB))
Write-Host ('  {0}  ({1:N0} KB)' -f $berkasZip, ((Get-Item -LiteralPath $berkasZip).Length / 1KB))
Write-Host "Salin juga folder '$Tujuan' ke flashdisk atau komputer lain secara berkala."
