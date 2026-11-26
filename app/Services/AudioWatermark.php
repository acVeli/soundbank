<?php

namespace App\Services;

use App\Models\LibraryItem;

/**
 * Filigrane des fichiers vendus : au téléchargement, chaque WAV / AIFF reçoit
 * des métadonnées propres à l'acheteur (clé de licence, nom, commande) et une
 * empreinte signée par APP_KEY. Le son lui-même n'est pas modifié.
 *
 * Le bloc est ajouté en fin de fichier (les formats RIFF et IFF autorisent les
 * blocs dans n'importe quel ordre) : l'original est recopié en flux, sans
 * charger le fichier en mémoire. Les autres formats sont livrés tels quels.
 *
 * Une fuite d'un fichier brut devient attribuable (commande soundbank:identify).
 * Un réenregistrement ou une conversion supprime ces métadonnées : c'est une
 * mesure de traçabilité et de dissuasion, pas un verrou.
 */
class AudioWatermark
{
    public const MARKER = 'SBWM';

    /**
     * Ce fichier peut-il recevoir le filigrane ?
     */
    public function supports(string $path): bool
    {
        return $this->container($path) !== null;
    }

    /**
     * Taille finale du fichier filigrané (pour l'en-tête Content-Length).
     */
    public function length(string $path, LibraryItem $item): int
    {
        $size = (int) filesize($path);

        if ($this->container($path) === null) {
            return $size;
        }

        return $size + ($size % 2) + strlen($this->trailer($path, $item));
    }

    /**
     * Écrit le fichier filigrané dans le flux de sortie.
     *
     * @param  resource  $out
     */
    public function write(string $path, LibraryItem $item, $out): void
    {
        $container = $this->container($path);
        $in = fopen($path, 'rb');

        if ($in === false) {
            return;
        }

        if ($container === null) {
            stream_copy_to_stream($in, $out);
            fclose($in);

            return;
        }

        $size = (int) filesize($path);
        $pad = $size % 2;
        $trailer = $this->trailer($path, $item);
        $bodySize = $size - 8 + $pad + strlen($trailer);

        fwrite($out, substr($container, 0, 4).($container === 'RIFF' ? pack('V', $bodySize) : pack('N', $bodySize)));
        fseek($in, 8);
        stream_copy_to_stream($in, $out);
        fclose($in);

        if ($pad) {
            fwrite($out, "\0");
        }

        fwrite($out, $trailer);
    }

    /**
     * Copie filigranée dans un fichier temporaire (pour les archives ZIP).
     */
    public function toTemporaryFile(string $path, LibraryItem $item): string
    {
        $temporary = (string) tempnam(sys_get_temp_dir(), 'sbwm');
        $out = fopen($temporary, 'wb');
        $this->write($path, $item, $out);
        fclose($out);
        touch($temporary, (int) filemtime($path));

        return $temporary;
    }

    /**
     * Lit l'empreinte d'un fichier (fuite présumée).
     *
     * @return array{v: int, lic: string, u: int, s: int, t: int, sig: string, valid: bool}|null
     */
    public function read(string $path): ?array
    {
        $bytes = (string) file_get_contents($path);
        $position = strrpos($bytes, self::MARKER.'{');

        if ($position === false) {
            return null;
        }

        $start = $position + strlen(self::MARKER);
        $end = strpos($bytes, '}', $start);
        $payload = $end === false ? null : json_decode(substr($bytes, $start, $end - $start + 1), true);

        if (! is_array($payload) || ! isset($payload['lic'], $payload['u'], $payload['s'], $payload['sig'])) {
            return null;
        }

        $payload['valid'] = hash_equals($this->signature((string) $payload['lic'], (int) $payload['u'], (int) $payload['s']), (string) $payload['sig']);

        return $payload;
    }

    /**
     * Bloc ajouté en fin de fichier : LIST/INFO lisible par les logiciels audio
     * (WAV) ou ANNO (AIFF), plus l'empreinte signée.
     */
    private function trailer(string $path, LibraryItem $item): string
    {
        $item->loadMissing(['user', 'sample.artist.artistProfile', 'orderItem.order']);
        $order = $item->orderItem?->order?->number;
        $artist = $item->sample?->artist?->displayName() ?? config('app.name');
        $comment = 'Licence '.$item->license_key.' — accordée à '.$item->user->name
            .($order ? ' — commande '.$order : '')
            .' — '.config('app.name').'. Revente et redistribution du son seul interdites.';

        $fingerprint = self::MARKER.json_encode([
            'v' => 1,
            'lic' => $item->license_key,
            'u' => $item->user_id,
            's' => $item->sample_id,
            't' => now()->timestamp,
            'sig' => $this->signature($item->license_key, $item->user_id, $item->sample_id),
        ], JSON_UNESCAPED_SLASHES);

        if ($this->container($path) === 'RIFF') {
            $info = 'INFO'
                .$this->riffChunk('ICMT', $comment."\0")
                .$this->riffChunk('ICOP', '© '.$artist.' — licence non exclusive '.$item->license_key."\0")
                .$this->riffChunk('ISFT', config('app.name')."\0");

            return $this->riffChunk('LIST', $info).$this->riffChunk('sbwm', $fingerprint);
        }

        return $this->iffChunk('ANNO', $comment).$this->iffChunk('ANNO', $fingerprint);
    }

    private function riffChunk(string $id, string $data): string
    {
        return $id.pack('V', strlen($data)).$data.(strlen($data) % 2 ? "\0" : '');
    }

    private function iffChunk(string $id, string $data): string
    {
        return $id.pack('N', strlen($data)).$data.(strlen($data) % 2 ? "\0" : '');
    }

    /**
     * « RIFF » (WAV), « FORM » (AIFF / AIFC) ou null si format non géré.
     */
    private function container(string $path): ?string
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        $header = (string) fread($handle, 12);
        fclose($handle);

        return match (true) {
            strlen($header) < 12 => null,
            str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WAVE' => 'RIFF',
            str_starts_with($header, 'FORM') && in_array(substr($header, 8, 4), ['AIFF', 'AIFC'], true) => 'FORM',
            default => null,
        };
    }

    private function signature(string $license, int $userId, int $sampleId): string
    {
        return substr(hash_hmac('sha256', $license.'|'.$userId.'|'.$sampleId, (string) config('app.key')), 0, 32);
    }
}
