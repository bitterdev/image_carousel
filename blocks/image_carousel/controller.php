<?php

namespace Concrete\Package\ImageCarousel\Block\ImageCarousel;

use Concrete\Core\Block\BlockController;
use Concrete\Core\Cache\Level\ExpensiveCache;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Entity\File\File;
use Concrete\Core\Entity\File\Image\Thumbnail\Type\Type as TypeEntity;
use Concrete\Core\Entity\File\Version;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\File\Image\Thumbnail\Type\Type;
use Concrete\Core\File\Set\Set;
use Concrete\Core\File\Set\SetList as FileSetList;
use Concrete\Core\File\Type\Type as FileType;
use Concrete\Core\File\FileList;
use Doctrine\DBAL\DBALException;

class Controller extends BlockController
{
    protected $btTable = 'btImageCarousel';
    protected $btInterfaceWidth = 400;
    protected $btInterfaceHeight = 500;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = false;
    protected $btCacheBlockOutputLifetime = 300;
    protected $btCacheBlockOutputOnPost = false;
    protected $btCacheBlockOutputForRegisteredUsers = false;
    protected $btExportFileFolderColumns = ["fileSetId"];

    /** @var int */
    protected $thumbnailType;
    /** @var int */
    protected $speed;
    /** @var int */
    protected $autoplaySpeed;
    /** @var int */
    protected $infinite;
    /** @var int */
    protected $dots;
    /** @var int */
    protected $autoplay;
    /** @var int */
    protected $fileSetId;
    /** @var int */
    protected $numberFiles;

    public function getBlockTypeDescription(): string
    {
        return t("Display images in a carousel.");
    }

    public function getBlockTypeName(): string
    {
        return t("Image Carousel");
    }

    public function registerViewAssets($outputContent = '')
    {
        parent::registerViewAssets($outputContent);

        $this->requireAsset('slick-slider');
    }

    public function view()
    {
        $this->set("fileSets", $this->getFileSets());
        $this->set("images", $this->getImages());
        $this->set("config", $this->getConfig());
        $this->set("thumbnailType", $this->getThumbnailType());
    }

    /**
     * @return string
     */
    public function getSearchableContent()
    {
        $content = "";

        $files = $this->getFiles();

        if (is_array($files)) {
            foreach ($files as $file) {
                if ($file instanceof File) {
                    $fileVersion = $file->getApprovedVersion();

                    if ($fileVersion instanceof Version) {
                        $content .= (strlen($content) > 0 ? " " : "") . $fileVersion->getTitle();
                    }
                }
            }
        }

        return $content;
    }

    public function add()
    {
        $this->initDefaults();
        $this->set("fileSets", $this->getFileSets());
        $this->set("fileSetId", null);
        $this->set("thumbnailType", null);
        $this->set("thumbnailTypes", $this->getThumbnailTypes());
        $this->set("breakpoints", $this->getInitialBreakpoints());
    }

    public function edit()
    {
        $this->set("fileSets", $this->getFileSets());
        $this->set("breakpoints", $this->getBreakpoints());
        $this->set("thumbnailTypes", $this->getThumbnailTypes());
    }

    /**
     * @param array $args
     */
    public function save($args)
    {
        if (!isset($args["dots"])) {
            $args["dots"] = 0;
        }

        if (!isset($args["infinite"])) {
            $args["infinite"] = 0;
        }

        if (!isset($args["autoplay"])) {
            $args["autoplay"] = 0;
        }

        parent::save($args);

        $breakpoints = $args["breakpoints"] ?? null;

        if (!is_null($breakpoints)) {
            $this->setBreakpoints($breakpoints);
        }

        // Clear Cache
        /** @var $cache ExpensiveCache */
        $cache = $this->app->make('cache/expensive');
        $cacheItem = $cache->getItem('bitter.image_carousel.images_' . $this->getBlockIdentifier());
        $cacheItem->clear();
    }

    /**
     * @param array $args
     * @return ErrorList
     */
    public function validate($args)
    {
        $errorList = new ErrorList();

        // Validate General Settings
        if (!isset($args["fileSetId"]) || !$this->isValidFileSet($args["fileSetId"])) {
            $errorList->add(t("The value for field %s is invalid.", t("File Set")));
        }

        if (!isset($args["thumbnailType"]) || !$this->isValidThumbnailType($args["thumbnailType"])) {
            $errorList->add(t("The value for field %s is invalid.", t("Image Type")));
        }

        if (!isset($args["speed"]) || !is_numeric($args["speed"]) || $args["speed"] < 0) {
            $errorList->add(t("The value for field %s is invalid.", t("Speed")));
        }

        if (!isset($args["autoplaySpeed"]) || !is_numeric($args["autoplaySpeed"]) || $args["autoplaySpeed"] < 0) {
            $errorList->add(t("The value for field %s is invalid.", t("Autoplay Speed")));
        }

        if (isset($args["autoplay"]) && intval($args["autoplay"]) !== 1) {
            $errorList->add(t("The value for field %s is invalid.", t("Autoplay")));
        }

        if (isset($args["infinite"]) && intval($args["infinite"]) !== 1) {
            $errorList->add(t("The value for field %s is invalid.", t("Infinite")));
        }

        if (isset($args["dots"]) && intval($args["dots"]) !== 1) {
            $errorList->add(t("The value for field %s is invalid.", t("Show Dots")));
        }

        if (isset($args["dots"]) && intval($args["dots"]) !== 1) {
            $errorList->add(t("The value for field %s is invalid.", t("Show Dots")));
        }

        // Validate Breakpoints
        $rowCounter = 0;

        if (is_array($args["breakpoints"])) {
            foreach ($args["breakpoints"] as $breakpoint) {
                $rowCounter++;

                if (!isset($breakpoint["breakpoint"]) || !is_numeric($breakpoint["breakpoint"]) || $breakpoint["breakpoint"] < 0) {
                    $errorList->add(t("The value for field %s in row %s is invalid.", t("Breakpoint"), $rowCounter));
                }

                if (!isset($breakpoint["slidesToShow"]) || !is_numeric($breakpoint["slidesToShow"]) || $breakpoint["slidesToShow"] < 0) {
                    $errorList->add(t("The value for field %s in row %s is invalid.", t("Slides to Show"), $rowCounter));
                }

                if (!isset($breakpoint["slidesToScroll"]) || !is_numeric($breakpoint["slidesToScroll"]) || $breakpoint["slidesToScroll"] < 0) {
                    $errorList->add(t("The value for field %s in row %s is invalid.", t("Slides to Scroll"), $rowCounter));
                }
            }
        } else {
            $errorList->add(t("Breakpoints are missing."));
        }

        return $errorList;
    }

    public function delete()
    {
        parent::delete();
        $this->removeBreakpoints();
    }

    /**
     * @param integer $newBID
     */
    public function duplicate($newBID)
    {
        parent::duplicate($newBID);
        $this->duplicateBreakpoints($newBID);
    }

    /**
     * @return TypeEntity|null
     */
    private function getThumbnailType()
    {
        if (strlen($this->thumbnailType) > 0) {
            return Type::getByHandle($this->thumbnailType);
        } else {
            return null;
        }
    }

    /**
     * @param string $thumbnailType
     *
     * @return boolean
     */
    private function isValidThumbnailType($thumbnailType)
    {
        return in_array($thumbnailType, array_keys($this->getThumbnailTypes()));
    }

    /**
     * @return array
     */
    private function getThumbnailTypes()
    {
        $thumbnailTypes = [];

        $thumbnailTypes[""] = t("Original Size");

        foreach (Type::getList() as $typeEntity) {
            $thumbnailTypes[$typeEntity->getHandle()] = $typeEntity->getName();
        }

        return $thumbnailTypes;
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        $highestBreakpoint = $this->getHighestBreakpoint();

        return [
            "infinite" => $this->infinite ? true : false,
            "dots" => $this->dots ? true : false,
            "speed" => (int)$this->speed,
            "arrows" => false,
            "lazyLoad" => "ondemand",
            "slidesToShow" => (int)$highestBreakpoint["slidesToShow"],
            "slidesToScroll" => (int)$highestBreakpoint["slidesToScroll"],
            "autoplay" => $this->autoplay ? true : false,
            "autoplaySpeed" => (int)$this->autoplaySpeed,
            "responsive" => $this->getResponsive()
        ];
    }

    /**
     * @return array
     */
    private function getHighestBreakpoint()
    {
        $highestBreakpoint = [];

        foreach ($this->getBreakpoints() as $breakpoint) {
            if (!isset($highestBreakpoint["breakpoint"]) || $breakpoint["breakpoint"] > $highestBreakpoint["breakpoint"]) {
                $highestBreakpoint = $breakpoint;
            }
        }

        return $highestBreakpoint;
    }

    /**
     * @return array
     */
    private function getResponsive()
    {
        $arrResponsive = [];

        foreach ($this->getBreakpoints() as $breakpoint) {
            array_push($arrResponsive, [
                "breakpoint" => (int)$breakpoint["breakpoint"],
                "settings" => [
                    "slidesToShow" => (int)$breakpoint["slidesToShow"],
                    "slidesToScroll" => (int)$breakpoint["slidesToScroll"]
                ]
            ]);
        }

        return $arrResponsive;
    }

    /**
     * @param int $fileSetId
     * @return boolean
     */
    private function isValidFileSet($fileSetId)
    {
        return in_array($fileSetId, array_keys($this->getFileSets()));
    }

    private function initDefaults()
    {
        $this->set("speed", 300);
        $this->set("autoplaySpeed", 2000);
        $this->set("autoplay", true);
        $this->set("infinite", false);
        $this->set("dots", true);
    }

    /**
     * @return array
     */
    private function getInitialBreakpoints()
    {
        return [
            [
                "breakpoint" => 1024,
                "slidesToShow" => 3,
                "slidesToScroll" => 3
            ],

            [
                "breakpoint" => 600,
                "slidesToShow" => 2,
                "slidesToScroll" => 2
            ],

            [
                "breakpoint" => 480,
                "slidesToShow" => 1,
                "slidesToScroll" => 1
            ]
        ];
    }

    /**
     * @return bool
     */
    private function hasBreakpoints()
    {
        /** @var Connection $db */
        $db = $this->app->make(Connection::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        return (int)$db->fetchColumn(
                "SELECT COUNT(*) FROM btImageCarouselBreakpoints WHERE bID = ?",
                [
                    $this->bID
                ]
            ) > 0;
    }

    /**
     * @return array
     */
    private function getBreakpoints()
    {
        /** @var Connection $db */
        $db = $this->app->make(Connection::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        return $db->fetchAll(
            "SELECT breakpoint, slidesToShow, slidesToScroll FROM btImageCarouselBreakpoints WHERE bID = ?",
            [
                $this->bID
            ]
        );
    }

    /**
     * @param array $breakpoints
     */
    private function setBreakpoints($breakpoints)
    {
        /** @var Connection $db */
        $db = $this->app->make(Connection::class);

        $this->removeBreakpoints();

        foreach ($breakpoints as $breakpoint) {
            try {
                /** @noinspection PhpUnhandledExceptionInspection */
                /** @noinspection SqlDialectInspection */
                /** @noinspection SqlNoDataSourceInspection */
                $db->executeQuery(
                    "INSERT INTO btImageCarouselBreakpoints (bID, breakpoint, slidesToShow, slidesToScroll) VALUES (?, ?, ?, ?)",

                    [
                        $this->bID,
                        (int)$breakpoint["breakpoint"],
                        (int)$breakpoint["slidesToShow"],
                        (int)$breakpoint["slidesToScroll"]
                    ]
                );
            } catch (DBALException $exception) {
                // Ignore
            }
        }
    }

    private function removeBreakpoints()
    {
        /** @var Connection $db */
        $db = $this->app->make(Connection::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $db->executeQuery(
            "DELETE FROM btImageCarouselBreakpoints WHERE bID = ?",

            [
                $this->bID
            ]
        );
    }

    /**
     * @param integer $newBID
     */
    private function duplicateBreakpoints($newBID)
    {
        /** @var Connection $db */
        $db = $this->app->make(Connection::class);

        foreach ($this->getBreakpoints() as $breakpoint) {

            try {
                /** @noinspection SqlDialectInspection */
                /** @noinspection SqlNoDataSourceInspection */
                $db->executeQuery(
                    "INSERT INTO btImageCarouselBreakpoints (bID, breakpoint, slidesToShow, slidesToScroll) VALUES (?, ?, ?, ?)",

                    [
                        $newBID,
                        $breakpoint["breakpoint"],
                        $breakpoint["slidesToShow"],
                        $breakpoint["slidesToScroll"]
                    ]
                );
            } catch (DBALException $exception) {
                // Ignore
            }
        }
    }

    private function getBlockIdentifier()
    {
        /** @noinspection PhpDeprecationInspection */
        return $this->getBlockObject()->getProxyBlock()
            ? $this->getBlockObject()->getProxyBlock()->getInstance()->getIdentifier()
            : $this->getIdentifier();
    }

    /**
     * @return array
     */
    private function getImages()
    {
        $images = [];

        $ttl = 24 * 60 * 60 * 30; // 1 month

        /** @var $cache ExpensiveCache */
        $cache = $this->app->make(ExpensiveCache::class);

        $cacheItem = $cache->getItem('bitter.image_carousel.images_' . $this->getBlockIdentifier());

        if ($cacheItem->isMiss()) {
            $cacheItem->lock();

            foreach ($this->getFiles() as $file) {
                if ($file instanceof File) {
                    $fileVersion = $file->getApprovedVersion();

                    if ($fileVersion instanceof Version) {
                        if ($this->getThumbnailType() instanceof TypeEntity) {
                            $imageUrl = $fileVersion->getThumbnailURL($this->getThumbnailType()->getBaseVersion());
                        } else {
                            $imageUrl = $fileVersion->getURL();
                        }

                        $images[] = [
                            "title" => $fileVersion->getTitle(),
                            "url" => $imageUrl
                        ];
                    }
                }
            }

            $cache->save($cacheItem->set($images)->expiresAfter($ttl));
        } else {
            $images = $cacheItem->get();
        }

        return $images;
    }

    /**
     * @return File[]
     */
    private function getFiles()
    {
        $files = [];

        /** @var $fileSet FileSetList */
        $fileSet = Set::getByID($this->fileSetId);

        if ($fileSet instanceof Set) {
            $fileList = new FileList();

            $fileList->filterBySet($fileSet);
            $fileList->filterByType(FileType::T_IMAGE);
            $fileList->sortByFileSetDisplayOrder();

            if ($this->numberFiles > 0) {
                $fileList->setItemsPerPage($this->numberFiles);
            } else {
                $fileList->setItemsPerPage(10000);
            }

            $files = $fileList->getResults();
        }

        return $files;
    }

    /**
     * @return array
     */
    private function getFileSets()
    {
        $fileSets = [];

        $fileSetList = new FileSetList();

        foreach ($fileSetList->get(1000, 0) as $fileSet) {
            if ($fileSet instanceof Set) {
                $fileSets[$fileSet->getFileSetID()] = $fileSet->getFileSetName();
            }
        }

        return $fileSets;
    }

}
