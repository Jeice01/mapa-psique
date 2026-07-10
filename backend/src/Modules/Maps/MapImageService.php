<?php

declare(strict_types=1);

namespace App\Modules\Maps;

use App\Database\Repositories\MapRepository;
use InvalidArgumentException;
use RuntimeException;

final class MapImageService
{
    private MapRepository $maps;

    public function __construct()
    {
        $this->maps = new MapRepository();
    }

    /**
     * Salva a imagem do mapa no disco e atualiza o registro no banco.
     *
     * @return string  Caminho relativo salvo (ex: "uploads/maps/map-{id}.jpg")
     */
    public function saveImage(string $mapId, string $ownerUserId, string $tmpPath, string $mime): string
    {
        $map = $this->maps->findByIdAndOwner($mapId, $ownerUserId);
        if ($map === null) {
            throw new InvalidArgumentException('Mapa não encontrado.');
        }

        $ext       = $this->mimeToExt($mime);
        $safeId    = preg_replace('/[^a-zA-Z0-9\-]/', '', $mapId);
        $filename  = "map-{$safeId}.{$ext}";
        $storageDir = $this->storageDir();
        $fullPath  = $storageDir . DIRECTORY_SEPARATOR . $filename;

        if (!is_dir($storageDir) && !mkdir($storageDir, 0750, true)) {
            throw new RuntimeException('Não foi possível criar o diretório de storage.');
        }

        if (!move_uploaded_file($tmpPath, $fullPath)) {
            throw new RuntimeException('Falha ao mover o arquivo enviado.');
        }

        $relativePath = 'uploads/maps/' . $filename;
        $this->maps->updateImagePath($mapId, $relativePath);

        return $relativePath;
    }

    /**
     * Retorna [conteúdo binário, mime-type] da imagem do mapa.
     *
     * @return array{0:string,1:string}
     */
    public function getImageContent(string $mapId, string $ownerUserId): array
    {
        $map = $this->maps->findByIdAndOwner($mapId, $ownerUserId);
        if ($map === null) {
            throw new InvalidArgumentException('Mapa não encontrado.');
        }

        $relativePath = $map['map_image_path'] ?? null;
        if ($relativePath === null || $relativePath === '') {
            throw new InvalidArgumentException('Este mapa não possui imagem.');
        }

        $fullPath = $this->storageDir() . DIRECTORY_SEPARATOR . basename($relativePath);
        if (!file_exists($fullPath)) {
            throw new InvalidArgumentException('Arquivo de imagem não encontrado.');
        }

        $content = file_get_contents($fullPath);
        if ($content === false) {
            throw new RuntimeException('Falha ao ler arquivo de imagem.');
        }

        $mime = mime_content_type($fullPath) ?: 'image/jpeg';
        return [$content, $mime];
    }

    /**
     * Retorna o caminho absoluto do diretório de armazenamento de imagens de mapas.
     */
    public function storageDir(): string
    {
        return dirname(__DIR__, 4) . '/storage/uploads/maps';
    }

    private function mimeToExt(string $mime): string
    {
        return match ($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'jpg',
        };
    }
}
