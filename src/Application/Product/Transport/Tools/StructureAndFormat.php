<?php
declare(strict_types=1);

namespace App\Application\Product\Transport\Tools;

class StructureAndFormat
{

    public function formatMedia(array $media): array
    {
        $formattedMedia = [];

        foreach ($media as $mediaItem) {
            $originalSource = $mediaItem['originalSource'] ?? '';
            $filename = $mediaItem['filename'] ?? '';

            $extension = pathinfo(parse_url($originalSource, PHP_URL_PATH), PATHINFO_EXTENSION);
            if ($extension && !str_ends_with($filename, ".$extension")) {
                $filename .= ".$extension";
            }

            $formattedMedia[] = [
                'originalSource' => $originalSource,
                'alt' => $mediaItem['alt'] ?? '',
                'contentType' => $mediaItem['mediaContentType'] ?? 'IMAGE',
                'filename' => $filename,
                'duplicateResolutionMode' => 'APPEND_UUID'
            ];
        }

        return $formattedMedia;
    }
    public function formatProductOptions(array $productOptions): array
    {
        $formattedOptions = [];
        foreach ($productOptions as $option) {
            $formattedOptions[] = [
                'name' => $option['name'],
                'position' => 1,
                'values' => array_map(fn($value) => ['name' => $value], $option['values']),
            ];
        }
        return $formattedOptions;
    }


    public function formatMetafields(array $metafields): array
    {
        $formattedMetafields = [];
        foreach ($metafields as $key => $value) {
            if ($value !== null) {
                $formattedMetafields[] = [
                    'key' => strtolower(str_replace(' ', '_', $key)),
                    'namespace' => 'global',
                    'value' => $this->convertValueToString($value),
                    'type' => 'single_line_text_field',
                ];
            }
        }
        return $formattedMetafields;
    }

    public function convertValueToString($value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map([$this, 'convertValueToString'], $value));
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string)$value;
    }
}