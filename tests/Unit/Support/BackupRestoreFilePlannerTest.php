<?php

namespace Tests\Unit\Support;

use App\Support\BackupRestoreFilePlanner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BackupRestoreFilePlannerTest extends TestCase
{
    public function testCurrentForkUploadDirectoriesAreAllowlisted(): void
    {
        $this->assertContains(
            'storage/private_uploads/component_instances',
            BackupRestoreFilePlanner::privateDirectories(),
        );
        $this->assertContains(
            'storage/private_uploads/model_numbers',
            BackupRestoreFilePlanner::privateDirectories(),
        );
        $this->assertContains(
            'storage/private_uploads/work_orders',
            BackupRestoreFilePlanner::privateDirectories(),
        );
        $this->assertContains(
            'storage/private_uploads/workflow_evidence',
            BackupRestoreFilePlanner::privateDirectories(),
        );
        $this->assertContains(
            'public/uploads/model_numbers',
            BackupRestoreFilePlanner::publicDirectories(),
        );
    }

    #[DataProvider('nestedArchivePathProvider')]
    public function testNestedArchivePathsKeepTheirRelativeDirectories(
        string $archivePath,
        string $expectedTarget,
    ): void {
        $this->assertSame(
            $expectedTarget,
            BackupRestoreFilePlanner::targetForArchivePath($archivePath),
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function nestedArchivePathProvider(): array
    {
        return [
            'workflow evidence' => [
                'backup/storage/private_uploads/workflow_evidence/results/42/evidence.jpg',
                'storage/private_uploads/workflow_evidence/results/42/evidence.jpg',
            ],
            'asset image' => [
                'backup/public/uploads/assets/17/asset-photo.png',
                'public/uploads/assets/17/asset-photo.png',
            ],
            'model-number image' => [
                'backup/public/uploads/model_numbers/8/model-number-photo.jpg',
                'public/uploads/model_numbers/8/model-number-photo.jpg',
            ],
            'component-instance attachment' => [
                'backup/storage/private_uploads/component_instances/attachment.pdf',
                'storage/private_uploads/component_instances/attachment.pdf',
            ],
            'Windows archive separators' => [
                'backup\\storage\\private_uploads\\work_orders\\2026\\invoice.pdf',
                'storage/private_uploads/work_orders/2026/invoice.pdf',
            ],
            'legacy private model directory' => [
                'backup/storage/private_uploads/assetmodels/manuals/model.pdf',
                'storage/private_uploads/models/manuals/model.pdf',
            ],
            'legacy private maintenance directory' => [
                'backup/storage/private_uploads/asset_maintenances/17/report.pdf',
                'storage/private_uploads/maintenances/17/report.pdf',
            ],
            'legacy public model directory' => [
                'backup/public/uploads/assetmodels/model.svg',
                'public/uploads/models/model.svg',
            ],
        ];
    }

    #[DataProvider('unsafeArchivePathProvider')]
    public function testUnsafeOrNonFilePathsAreNotPlanned(string $archivePath): void
    {
        $this->assertNull(BackupRestoreFilePlanner::targetForArchivePath($archivePath));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeArchivePathProvider(): array
    {
        return [
            'parent traversal' => [
                'backup/storage/private_uploads/assets/../../.env',
            ],
            'current directory traversal' => [
                'backup/storage/private_uploads/assets/./asset.jpg',
            ],
            'empty segment' => [
                'backup/storage/private_uploads/assets/nested//asset.jpg',
            ],
            'directory entry' => [
                'backup/storage/private_uploads/workflow_evidence/results/',
            ],
            'outside allowlist' => [
                'backup/storage/framework/cache/data.txt',
            ],
            'control character' => [
                "backup/public/uploads/assets/17/photo\0.jpg",
            ],
            'hidden file' => [
                'backup/public/uploads/assets/.htaccess.jpg',
            ],
            'Windows alternate data stream' => [
                'backup/public/uploads/assets/photo.jpg:payload.php',
            ],
            'Windows parent alias' => [
                'backup/public/uploads/assets/.. /payload.jpg',
            ],
            'Windows reserved device name' => [
                'backup/public/uploads/assets/CON.jpg',
            ],
        ];
    }

    #[DataProvider('extensionProvider')]
    public function testExtensionsAreRestrictedByRestorePurpose(
        string $targetPath,
        bool $expected,
    ): void {
        $this->assertSame(
            $expected,
            BackupRestoreFilePlanner::hasAllowedExtension(
                $targetPath,
                ['jpg', 'pdf', 'svg', 'xml'],
            ),
        );
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function extensionProvider(): array
    {
        return [
            'public image' => ['public/uploads/assets/photo.jpg', true],
            'public SVG' => ['public/uploads/models/diagram.SVG', true],
            'public XML' => ['public/uploads/assets/payload.xml', false],
            'public executable script' => ['public/uploads/assets/payload.php', false],
            'private PDF' => ['storage/private_uploads/assets/invoice.pdf', true],
            'private XML' => ['storage/private_uploads/assets/export.xml', true],
            'private CSV' => ['storage/private_uploads/imports/export.csv', true],
            'legacy extensionless import CSV' => ['storage/private_uploads/imports/exportcsv', true],
            'private executable script' => ['storage/private_uploads/assets/payload.php', false],
            'OAuth key' => ['storage/oauth-private.key', true],
            'outside restore roots' => ['storage/framework/cache/file.jpg', false],
        ];
    }
}
