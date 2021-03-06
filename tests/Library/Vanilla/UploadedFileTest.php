<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Garden\EventManager;
use Garden\SafeCurl\Exception\InvalidURLException;
use Vanilla\UploadedFile;
use VanillaTests\SharedBootstrapTestCase;

/**
 * Tests for uploaded files.
 */
class UploadedFileTest extends SharedBootstrapTestCase {

    /**
     * Test that various internal IPs cannot be redirected too.
     *
     * @param string $blacklistedAddress
     *
     * @dataProvider blacklistProvider
     */
    public function testCreateFromRemoteBlacklist(string $blacklistedAddress) {
        $this->expectException(InvalidURLException::class);
        UploadedFile::fromRemoteResourceUrl($blacklistedAddress);
    }

    /**
     * @return array
     */
    public function blacklistProvider(): array {
        return [
            ['0.0.0.0/8'],
            ["file:///etc/passwd"],
            ["gopher://localhost"],
            ["telnet://localhost:25"],
        ];
    }

    /**
     * Test that redirects are followed.
     */
    public function testSavesRemoteUrls() {
        $file = UploadedFile::fromRemoteResourceUrl('http://vanillaforums.com');
        $this->assertEquals('http://vanillaforums.com', $file->getForeignUrl());
        $this->assertEquals('https://vanillaforums.com/en/', $file->getResolvedForeignUrl());

        // Ensure we've temporarily stashed the file somewhere.
        $this->assertTrue(file_exists($file->getFile()));
    }

    /**
     * Test file persistence.
     */
    public function testPersistFile() {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl('https://vanillaforums.com/svgs/logo.svg');

        // Ensure we've temporarily stashed the file somewhere.
        $this->assertFileExists($file->getFile());

        // Save the upload.
        $file->persistUpload();
        $this->assertFileNotExists($file->getFile(), 'The original upload is moved and cleaned up.');
        $this->assertFileExists(PATH_UPLOADS.'/'.$file->getPersistedPath(), 'Final upload file is persisted');

        $this->assertStringContainsString('migrated/', $file->getPersistedPath(), 'Persisted remote files should contain "/migrated/"');
        $this->assertStringContainsString($file->getClientFilename(), $file->getPersistedPath(), 'Persisted remote files should the real name.');
    }

    /**
     * Test that custom paths can be persisted.
     */
    public function testCustomPersistedPath() {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl('https://vanillaforums.com/svgs/logo.svg');

        // Ensure we've temporarily stashed the file somewhere.
        $this->assertFileExists($file->getFile());

        // Save the upload.
        $file->persistUpload(false, 'subdir', 'prefix-%s');
        $this->assertFileExists(PATH_UPLOADS.'/'.$file->getPersistedPath(), 'Final upload file is persisted');
        $this->assertStringMatchesFormat('subdir/%d/prefix-logo.svg', $file->getPersistedPath());
    }

    /**
     * Test copying of a file.
     */
    public function testCopying() {
        // Perform some tests related to saving uploads.
        $file = UploadedFile::fromRemoteResourceUrl('https://vanillaforums.com/svgs/logo.svg');

        // Ensure we've temporarily stashed the file somewhere.
        $this->assertFileExists($file->getFile());

        // Save the upload.
        $file->persistUpload(true, 'copied');
        $this->assertFileExists(PATH_UPLOADS.'/'.$file->getPersistedPath(), 'Final upload file is persisted');
        $this->assertFileExists($file->getFile(), 'Original file is not deleted');
    }

    /**
     * Test that an event handler can completely handle the persistance.
     */
    public function testPersistEventHandling() {
        /** @var EventManager $eventManager */
        $eventManager = self::container()->get(EventManager::class);
        $expectedSaveName = 'custom/save/name.result';

        $eventManager->bind('gdn_upload_saveAs', function ($upload, $args) use ($expectedSaveName) {
            $args['Handled'] = true;
            $args['Parsed']['SaveName'] = $expectedSaveName;
        });

        $file = UploadedFile::fromRemoteResourceUrl('https://vanillaforums.com/svgs/logo.svg');
        $file->persistUpload();

        // Standard cleanup/moving procedures did not occur.
        $this->assertFileExists($file->getFile());
        $this->assertEquals($expectedSaveName, $file->getPersistedPath());
    }
}
