<?php
declare(strict_types=1);
namespace SourceBroker\T3api\Service;

use InvalidArgumentException;
use SourceBroker\T3api\Domain\Model\AbstractOperation;
use SourceBroker\T3api\Domain\Model\UploadSettings;
use Symfony\Bridge\PsrHttpMessage\Factory\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class FileUploadService
 */
class FileUploadService implements SingletonInterface
{
    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @param ResourceFactory $resourceFactory
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory): void
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @param AbstractOperation $operation
     * @param Request $request
     * @throws Exception
     * @return File
     */
    public function process(AbstractOperation $operation, Request $request): File
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('originalResource');
        $uploadSettings = $operation->getUploadSettings();

        $this->verifyFileExtension($uploadSettings, $uploadedFile);

        return $this->getUploadFolder($uploadSettings)
            ->addUploadedFile(
                [
                    'error' => $uploadedFile->getError(),
                    'name' => $uploadedFile->getClientOriginalName(),
                    'size' => $uploadedFile->getSize(),
                    'tmp_name' => $uploadedFile->getPathname(),
                    'type' => $uploadedFile->getMimeType(),
                ],
                $operation->getUploadSettings()->getConflictMode()
            );
    }

    /**
     * @param UploadSettings $uploadSettings
     * @param UploadedFile $uploadedFile
     * @throws InvalidArgumentException
     * @return void
     */
    protected function verifyFileExtension(UploadSettings $uploadSettings, UploadedFile $uploadedFile): void
    {
        if (!GeneralUtility::verifyFilenameAgainstDenyPattern($uploadedFile->getClientOriginalName())) {
            throw new InvalidArgumentException(
                'Uploading files with PHP file extensions is not allowed!',
                1576999829435
            );
        }

        if (!empty($uploadSettings->getAllowedFileExtensions())) {
            $filePathInfo = PathUtility::pathinfo($uploadedFile->getClientOriginalName());
            if (!in_array(
                strtolower($filePathInfo['extension']),
                $uploadSettings->getAllowedFileExtensions(),
                true
            )) {
                throw new InvalidArgumentException(
                    sprintf(
                        'File extension `%s` is not allowed. Allowed file extensions are: `%s`',
                        strtolower($filePathInfo['extension']),
                        implode(', ', $uploadSettings->getAllowedFileExtensions())
                    ),
                    1577000112816
                );
            }
        }
    }

    /**
     * Creates upload folder if it not exists yet and returns it
     *
     * @param UploadSettings $uploadSettings
     *
     * @throws ExistingTargetFolderException
     * @throws InsufficientFolderAccessPermissionsException
     * @throws InsufficientFolderWritePermissionsException
     * @return Folder
     */
    protected function getUploadFolder(UploadSettings $uploadSettings): Folder
    {
        $uploadFolder = null;

        try {
            $uploadFolder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier(
                $uploadSettings->getFolder()
            );
        } catch (ResourceDoesNotExistException $exception) {
            $resource = $this->resourceFactory->getStorageObjectFromCombinedIdentifier(
                $uploadSettings->getFolder()
            );

            if (!$resource instanceof ResourceStorage) {
                throw new InvalidArgumentException(
                    sprintf('Invalid upload path (`%s`). Storage does not exist?', $uploadSettings->getFolder()),
                    1577262016243
                );
            }

            $path = explode('/', $uploadSettings->getFolder());

            // removes storage identifier
            array_shift($path);

            do {
                $directoryName = array_shift($path);

                if ($uploadFolder && $resource->hasFolderInFolder($directoryName, $uploadFolder)) {
                    $uploadFolder = $resource->getFolderInFolder($directoryName, $uploadFolder);
                } elseif (!$uploadFolder && $resource->hasFolder($directoryName)) {
                    $uploadFolder = $resource->getFolder($directoryName);
                } else {
                    $uploadFolder = $resource->createFolder($directoryName, $uploadFolder);
                }
            } while (count($path));
        }

        if (!$uploadFolder instanceof Folder) {
            throw new InvalidArgumentException(
                sprintf(
                    'Can not upload - `%s` is not a folder and could not create it.',
                    $uploadSettings->getFolder()
                ),
                1577001080960
            );
        }

        return $uploadFolder;
    }
}
