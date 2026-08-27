<?php

namespace App\Support;

final class BackupRestoreFilePlanner
{
    /**
     * @return array<string, string>
     */
    public static function privateDirectoryMappings(): array
    {
        return [
            'storage/private_uploads/accessories' => 'storage/private_uploads/accessories',
            'storage/private_uploads/assetmodels' => 'storage/private_uploads/models',
            'storage/private_uploads/asset_maintenances' => 'storage/private_uploads/maintenances',
            'storage/private_uploads/maintenances' => 'storage/private_uploads/maintenances',
            'storage/private_uploads/models' => 'storage/private_uploads/models',
            'storage/private_uploads/assets' => 'storage/private_uploads/assets',
            'storage/private_uploads/audits' => 'storage/private_uploads/audits',
            'storage/private_uploads/components' => 'storage/private_uploads/components',
            'storage/private_uploads/component_instances' => 'storage/private_uploads/component_instances',
            'storage/private_uploads/consumables' => 'storage/private_uploads/consumables',
            'storage/private_uploads/eula-pdfs' => 'storage/private_uploads/eula-pdfs',
            'storage/private_uploads/imports' => 'storage/private_uploads/imports',
            'storage/private_uploads/locations' => 'storage/private_uploads/locations',
            'storage/private_uploads/licenses' => 'storage/private_uploads/licenses',
            'storage/private_uploads/model_numbers' => 'storage/private_uploads/model_numbers',
            'storage/private_uploads/signatures' => 'storage/private_uploads/signatures',
            'storage/private_uploads/users' => 'storage/private_uploads/users',
            'storage/private_uploads/work_orders' => 'storage/private_uploads/work_orders',
            'storage/private_uploads/workflow_evidence' => 'storage/private_uploads/workflow_evidence',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function privateDirectories(): array
    {
        return array_keys(self::privateDirectoryMappings());
    }

    /**
     * @return array<string, string>
     */
    public static function publicDirectoryMappings(): array
    {
        return [
            'public/uploads/accessories' => 'public/uploads/accessories',
            'public/uploads/assetmodels' => 'public/uploads/models',
            'public/uploads/maintenances' => 'public/uploads/maintenances',
            'public/uploads/assets' => 'public/uploads/assets',
            'public/uploads/avatars' => 'public/uploads/avatars',
            'public/uploads/categories' => 'public/uploads/categories',
            'public/uploads/companies' => 'public/uploads/companies',
            'public/uploads/components' => 'public/uploads/components',
            'public/uploads/consumables' => 'public/uploads/consumables',
            'public/uploads/departments' => 'public/uploads/departments',
            'public/uploads/locations' => 'public/uploads/locations',
            'public/uploads/manufacturers' => 'public/uploads/manufacturers',
            'public/uploads/model_numbers' => 'public/uploads/model_numbers',
            'public/uploads/models' => 'public/uploads/models',
            'public/uploads/suppliers' => 'public/uploads/suppliers',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function publicDirectories(): array
    {
        return array_keys(self::publicDirectoryMappings());
    }

    /**
     * Resolve an allowlisted archive entry to its application-relative restore path.
     */
    public static function targetForArchivePath(string $archivePath): ?string
    {
        $archivePath = str_replace('\\', '/', $archivePath);

        $directoryMappings = array_merge(
            self::privateDirectoryMappings(),
            self::publicDirectoryMappings(),
        );

        foreach ($directoryMappings as $sourceDirectory => $targetDirectory) {
            $marker = $sourceDirectory . '/';
            $directoryPosition = strrpos($archivePath, $marker);

            if ($directoryPosition === false) {
                continue;
            }

            $relativePath = substr($archivePath, $directoryPosition + strlen($marker));

            if (! self::isSafeRelativeFilePath($relativePath)) {
                return null;
            }

            return $targetDirectory . '/' . $relativePath;
        }

        return null;
    }

    /**
     * Limit restored uploads to the same extensions accepted by the application.
     *
     * Private uploads additionally permit CSV/key backups. XML remains private-only
     * because restoring it below the public web root would create an avoidable
     * active-content surface.
     *
     * @param array<int, string> $allowedUploadExtensions
     */
    public static function hasAllowedExtension(string $targetPath, array $allowedUploadExtensions): bool
    {
        $targetPath = str_replace('\\', '/', $targetPath);
        $extension = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        $allowedUploadExtensions = array_values(array_unique(array_map(
            static fn (string $allowedExtension): string => strtolower(ltrim($allowedExtension, '.')),
            $allowedUploadExtensions,
        )));

        if (str_starts_with($targetPath, 'public/uploads/')) {
            return $extension !== 'xml'
                && in_array($extension, $allowedUploadExtensions, true);
        }

        if (
            ! str_starts_with($targetPath, 'storage/private_uploads/')
            && ! in_array($targetPath, ['storage/oauth-private.key', 'storage/oauth-public.key'], true)
        ) {
            return false;
        }

        if (
            str_starts_with($targetPath, 'storage/private_uploads/imports/')
            && $extension === ''
            && str_ends_with(strtolower(basename($targetPath)), 'csv')
        ) {
            return true;
        }

        return in_array(
            $extension,
            array_merge($allowedUploadExtensions, ['csv', 'key']),
            true,
        );
    }

    private static function isSafeRelativeFilePath(string $path): bool
    {
        if ($path === '' || str_ends_with($path, '/')) {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            return false;
        }

        foreach (explode('/', $path) as $segment) {
            if (
                $segment === ''
                || $segment === '.'
                || $segment === '..'
                || str_starts_with($segment, '.')
                || trim($segment) !== $segment
                || str_ends_with($segment, '.')
                || preg_match('/[<>:"|?*]/', $segment) === 1
                || preg_match('/^(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|$)/i', $segment) === 1
            ) {
                return false;
            }
        }

        return true;
    }
}
