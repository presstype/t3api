.. include:: ../Includes.txt


.. _handling_file_upload:

=======================
Handling File Upload
=======================

Creating file resource
=======================

To create uploadable resource it is needed to create ``POST`` endpoint for resource which class extends
``\TYPO3\CMS\Extbase\Domain\Model\File``.

.. code-block:: php

   declare(strict_types=1);
   namespace Vendor\Users\Domain\Model;

   use SourceBroker\T3api\Annotation as T3api;

   /**
    * @T3api\ApiResource (
    *     collectionOperations={
    *          "post"={
    *              "path"="/files",
    *              "method"="POST",
    *          },
    *     }
    * )
    */
   class File extends \TYPO3\CMS\Extbase\Domain\Model\File
   {
   }

There are 3 configurable options for uploadable endpoint:
- ``folder`` - destination folder (default: ``1:/user_upload/`` which means files will be uploaded into
``user_upload`` directory of file storage ID ``1``).
- ``allowedFileExtensions`` - Array of allowed file extensions (default:
``$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']``).
- ``conflictMode`` - Value of enumeration ``\TYPO3\CMS\Core\Resource\DuplicationBehavior`` (default:
``\TYPO3\CMS\Core\Resource\DuplicationBehavior::RENAME`` which means that new file name will be changed if same file
already exists).

.. code-block:: php

   declare(strict_types=1);
   namespace Vendor\Users\Domain\Model;

   use SourceBroker\T3api\Annotation as T3api;

   /**
    * @T3api\ApiResource (
    *     collectionOperations={
    *          "post"={
    *              "path"="/files",
    *              "method"="POST",
    *          },
    *     },
    *     attributes={
    *          "upload"={
    *              "folder"="1:/user_upload/",
    *              "allowedFileExtensions"={"jpg", "jpeg", "png"},
    *              "conflictMode"=DuplicationBehavior::RENAME,
    *          }
    *     }
    * )
    */
   class File extends \TYPO3\CMS\Extbase\Domain\Model\File
   {
   }

Configuring TCA
================

It may be needed to adjust ``TCA`` configuration to correctly fill ``sys_file_reference`` columns. Correct ``TCA``
configuration contains at least 3 elements inside ``foreign_match_fields`` array - ``fieldname``, ``tablenames`` and
``table_local`` but extension builder by default creates only ``fieldname`` (at least in current version).

.. code-block:: php

   $GLOBALS['TCA']['tx_users_domain_model_user']['columns']['photo']['config']['foreign_match_fields']['fieldname'] = 'photo';
   $GLOBALS['TCA']['tx_users_domain_model_user']['columns']['photo']['config']['foreign_match_fields']['tablenames'] = 'tx_users_domain_model_user';
   $GLOBALS['TCA']['tx_users_domain_model_user']['columns']['photo']['config']['foreign_match_fields']['table_local'] = 'sys_file';

Appropriate ``TCA`` configuration for uploadable field may look like code below. Mind that
``\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig`` adds element ``fieldname`` by default
so it is needed only to take care of ``tablenames`` and ``table_local``.

.. code-block:: php

        'photo' => [
            'exclude' => true,
            'label' => 'LLL:EXT:users/Resources/Private/Language/locallang_db.xlf:tx_users_domain_model_user.photo',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'photo',
                [
                    'foreign_match_fields' => [
                        'tablenames' => 'tx_users_domain_model_user',
                        'table_local' => 'sys_file',
                    ],
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                    ],
                    'foreign_types' => [
                        '0' => [
                            'showitem' => '
                            --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                            'showitem' => '
                            --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                            'showitem' => '
                            --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                            'showitem' => '
                            --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                            'showitem' => '
                            --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                            'showitem' => '
                            --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                        ]
                    ],
                    'maxitems' => 1
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            ),
        ],

File upload request
====================

@todo

.. code::

   $ curl --request POST 'https://intranet-rauch-cc.devloc.site/_api/files' \
      --header 'Accept-Language: en-US,en;q=0.9,pl;q=0.8,fr;q=0.7,de;q=0.6' \
      --header 'Content-Type: application/x-www-form-urlencoded' \
      --header 'Cookie: _ga=GA1.2.1308859886.1571651255; _fbp=fb.1.1571661354813.2112998372; user_allowed_save_cookie=yes; PHPSESSID=vlg2c6834as4ibtv70erfnejmi; tx_t3adminer=c6lfrsapvnggnhhfspmrbi7kap; Typo3InstallTool=ar1hmhjp2td1755b5q0iuusns3; cookieconsent_status=allow; tx_restrictfe=75777619; fe_typo_user=714ae28f4a30264890442ef72c72ddb2; be_typo_user=dff2c4da936075df18bbe6c6842cdf7b; io=kybGWw0E6Sq4xkFfAAAz' \
      --form 'originalResource=@/Users/mrf/Desktop/Media/__EXAMPLE/nature31.jpg'

@todo request with multiple files (ObjectStorage with FileReference)

File upload response
=====================

@todo

Save reference to new file
===========================

.. important::
    It is not (yet) possible to update existing file reference within T3api request - it is possible only to create
    new reference.

.. code-block:: json

   {
      "photo": {
         "uidLocal": 15,
      }
   }

If you would like to save any other data inside file reference it is needed to extend
``TYPO3\CMS\Extbase\Domain\Model\FileReference`` class.

.. code-block:: json

   {
      "falMedia": [
         {
            "uidLocal": 15,
            "showinpreview": 1
         },
         {
            "uidLocal": 16,
            "showinpreview": 0
         }
      ]
   }

@todo information about handling custom class of file reference (which extends standard extbase FileReference)

@todo

Removing single file reference
===============================

To remove existing file reference it is needed to send value `0`. **Because of extbase and JMS serializer limitations sending `NULL` will not remove existing file reference**. "Extbase limitation" means that existing file references are not removed when persisting empty value instead of file reference object (column for property in entity is cleared but file reference is kept). "JMS serializer limitations" means  that JMS does not allow to apply custom subscribers and handlers when `NULL` is sent.

.. code-block:: php

   declare(strict_types=1);
   namespace Vendor\User\Domain\Model;

   use SourceBroker\T3api\Annotation as T3api;
   use TYPO3\CMS\Extbase\Domain\Model\FileReference;

   /**
    * @T3api\ApiResource (
    *     itemOperations={
    *          "patch"={
    *              "path"="/users/{id}",
    *              "method"="PATCH",
    *          }
    *     },
    * )
    */

   class User extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
   {
       /**
        * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
        */
       protected $avatar = null;

       public function getAvatar(): ?FileReference
       {
           return $this->avatar;
       }

       public function setAvatar(?FileReference $avatar): void
       {
           $this->avatar = $avatar;
       }
   }

To remove file from model definition above we need to send a JSON payload as follows to ``PATCH`` ``/users/X`` endpoint to remove image.

.. code-block:: json

   {
      "avatar": 0
   }

Removing collection file reference
====================================

To remove collection file reference it is needed to send array with new elements. If array is empty - all elements will be removed.

.. code-block:: php

   declare(strict_types=1);
   namespace Vendor\News\Domain\Model;

   use SourceBroker\T3api\Annotation as T3api;
   use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

   /**
    * @T3api\ApiResource (
    *     itemOperations={
    *          "patch"={
    *              "path"="/news/{id}",
    *              "method"="PATCH",
    *          },
    *     }
    * )
    */
   class News extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
   {
       /**
        * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
        */
       protected $falMedia;

       public function __construct()
       {
           $this->falMedia = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
       }

       /**
        * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
        */
       public function getFalMedia(): ObjectStorage
       {
           return $this->falMedia;
       }

       /**
        * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $falMedia
        */
       public function setFalMedia(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $falMedia): void
       {
           $this->falMedia = $falMedia;
       }

       /***
        * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $falMedia
        */
       public function addFalMedia(\TYPO3\CMS\Extbase\Domain\Model\FileReference $falMedia): void
       {
           $this->falMedia->attach($falMedia);
       }
   }

To remove files from model definition above we need to send a JSON payload as follows to ``PATCH`` ``/news/X`` endpoint to remove image.

.. code-block:: json

   {
      "falMedia": []
   }
