<?php


namespace VK;

require_once 'bootstrap.php';

class vk
{
    const UPLOAD_PRODUCT_PART_COUNT = 100;

    /**
     * @var \Registry
     */
    protected $registry;

    /**
     * @var \VKApiClient
     */
    protected $vkApiClient;

    /**
     * @var \Logger
     */
    protected $logger;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $oath;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $optionValues;

    /**
     * @var bool
     */
    public static $history_run = false;

    /**
     * vk constructor.
     *
     * @param $registry
     */
    public function __construct($registry) {
        $this->registry = $registry;
        $this->load->model('setting/setting');
        $this->load->model('extension/vk/tables');

        $this->vkApiClient = $this->getApiClient();
        $this->logger = $this->getLogger();
        $this->settings = $this->model_setting_setting->getSetting('vk_settings');
        $this->oath = $this->model_setting_setting->getSetting('vk_oath');
    }

    /**
     * @param $name
     *
     * @return mixed|null
     */
    public function __get($name) {
        return $this->registry->get($name);
    }

    /**
     * Get api client object
     *
     * @param null $accessTokenUser
     * @param null $accessTokenGroup
     *
     * @return bool|\VKApiClient
     */
    public function getApiClient($accessTokenUser = null, $accessTokenGroup = null)
    {
        $this->load->model('setting/setting');
        $setting = $this->model_setting_setting->getSetting('vk_oath');

        if ($accessTokenUser === null) {
            $accessTokenUser = isset($setting['vk_oath_access_token']) ? $setting['vk_oath_access_token'] : '';
        }

        if ($accessTokenGroup === null) {
            $accessTokenGroup = isset($setting['vk_oath_access_token_group']) ? $setting['vk_oath_access_token_group'] : '';
        }

        if ($accessTokenUser && $accessTokenGroup) {
            return new \VKApiClient($accessTokenUser, $accessTokenGroup);
        }

        return false;
    }

    /**
     * Get opencart api client
     *
     * @param object $registry
     *
     * @return \OpencartApiClient
     */
    public function getOcApiClient($registry) {
        return new \OpencartApiClient($registry);
    }

    /**
     * Get Logger
     *
     * @param string $logPath
     * @param int $files
     *
     * @return \Logger
     */
    public function getLogger(/*$logPath = DIR_LOGS, $files = 10*/)
    {
        return new \Logger(/*$logPath, $files*/);
    }

    /**
     * Create albums
     *
     * @throws \VKApiException
     * @throws \VKApiMarketTooManyAlbumsException
     * @throws \VKClientException
     */
    public function createAlbum()
    {
        $categoriesForExport = $this->getCategoryChecked();

        foreach ($categoriesForExport as &$categoryForExport) {
            if (isset($categoryForExport['album_id'])) {

                continue;
            }

            $data = [
                'owner_id' => (int)$this->oath['vk_oath_id_group'],
                'title' => htmlspecialchars_decode($categoryForExport['vk_title'])
//                'photo_id' => !empty($categoryForExport['pathImage']) ? $this->getImageId($categoryForExport['pathImage'], $categoryForExport['category_id'], 'album') : null
//                'main_album' => ''
            ];

            $album = $this->vkApiClient->market()->addAlbum($data);
            $categoryForExport['album_id'] = (int)$album['market_album_id'];

            $this->putTableAlbums($categoryForExport, $album);
        }

        unset($categoryForExport);

        return $categoriesForExport;
    }

    /**
     * Get categories selected for export
     *
     * @return array
     */
    private function getCategoryChecked()
    {
        $this->load->model('catalog/category');

        $categoriesChecked = array();

        if (isset($this->settings['vk_settings_category-list'])) {
            foreach ($this->settings['vk_settings_category-list'] as $setting_category) {
                $category = $this->model_catalog_category->getCategory((int) $setting_category['category_id']);

                $categoriesChecked[$setting_category['vk_title']] = array(
                    'category_id' => (int)$category['category_id'],
                    'parent_id' => (int)$category['parent_id'],
                    'vk_title' => $setting_category['vk_title'],
                    'city_filter' => $setting_category['city_filter'],
                    'pathImage' => $category['image']
                );
            }
        }

        return $this->checkCategories($categoriesChecked);
    }

    /**
     * Checking for the existence of an album
     *
     * @param $categoriesChecked
     * @throws \VKApiException
     * @throws \VKClientException
     *
     * @return array
     */
    private function checkCategories($categoriesChecked)
    {
        $albumsFromBase = $this->getAlbumsFromBaseAndExistsInVk();

        foreach ($albumsFromBase as $oneAlbumFromBase) {
            if (!key_exists($oneAlbumFromBase['vk_title'], $categoriesChecked)) { // Удаляем лишние связки, которых уже нет в настройках 
                $this->deleteAlbum($oneAlbumFromBase);
            } else {
                $categoriesChecked[$oneAlbumFromBase['vk_title']]['album_id'] = $oneAlbumFromBase['vk_id'];
            }
        }

        return $categoriesChecked;
    }

    /**
     * Checking the relevance of the database
     *
     * @return array
     * @throws \VKApiException
     * @throws \VKClientException
     */
    private function getAlbumsFromBaseAndExistsInVk()
    {
        $albumsFromVk = $this->getAlbums();
        $albumsFromBase = $this->model_extension_vk_tables->albums()->getAll();

        if (!empty($albumsFromBase)) { // Удалим связки с ВК альбомами для тех, которые удалили в ВК
            foreach ($albumsFromBase as $key => $curAlbumFromBase) {
                if (!key_exists($curAlbumFromBase['vk_id'], $albumsFromVk)) {
                    $this->model_extension_vk_tables->albums()->delete($curAlbumFromBase[DB_PREFIX . 'id']);

                    unset($albumsFromBase[$key]);
                }
            }
        } elseif (!empty($albumsFromVk)) { // Если еще нет связок с ВК альбомами в нашей БД
            /* 

            Закооментировал создание связок по альбомам в ВК - такой логики не нужно, 
            так как у нас будет не создание по имени, логика сложнее, другое имя и город, поэтому создание будет в одну сторону от нас в ВК

            $albumsFromBase = array();

            foreach ($albumsFromVk as $vk_id => $name) { // Сделаем связки по имени ВК альбома
                $this->load->model('extension/vk/references');

                $oc_categories = $this->model_catalog_category->getCategories(array('filter_name' => $name));

                if (!empty($oc_categories[0])) {
                    $this->putTableAlbums(
                        array(
                            'category_id' => $oc_categories[0]['category_id'],
                            'parent_id' => $oc_categories[0]['parent_id'],
                            'name' => $oc_categories[0]['name']
                        ),
                        array(
                            'market_album_id' => $vk_id
                        )
                    );

                    $albumsFromBase[] = array(
                        'oc_id' => $oc_categories[0]['category_id'],
                        'oc_parent_id' => $oc_categories[0]['parent_id'],
                        'oc_name' => $oc_categories[0]['name'],
                        'vk_id' => $vk_id,
                    );
                }
            }
            */
        }

        return $albumsFromBase;
    }

    /**
     * Delete album from database and vk
     *
     * @param $category
     * @throws \VKApiException
     * @throws \VKApiMarketAlbumNotFoundException
     * @throws \VKClientException
     */
    private function deleteAlbum($category)
    {
        $this->vkApiClient->market()->deleteAlbum([
            'owner_id' => (int)$this->oath['vk_oath_id_group'],
            'album_id' => (int)$category['vk_id']
        ]);

        $this->model_extension_vk_tables->albums()->delete($category[DB_PREFIX . 'id'], $category[DB_PREFIX . 'parent_id']);
        $this->model_extension_vk_tables->images()->delete($category[DB_PREFIX . 'id'], 'album');
    }

    /**
     * Get all albums from vk
     *
     * @throws \VKApiException
     * @throws \VKClientException
     *
     * @return array
     */
    private function getAlbums()
    {
        $albums = array();

        $response = $this->vkApiClient->market()->getAlbums([
            'owner_id' => (int)$this->oath['vk_oath_id_group'],
            'count' => 100,
        ]);

        foreach ($response['items'] as $item) {         
            $albums[$item['id']] = $item['title'];
        }

        return $albums;
    }

    /**
     * Get image id
     *
     * @param  string $pathImage
     * @param  int $source_id
     * @param  string $flag
     *
     * @throws \VKApiException
     * @throws \VKApiParamHashException
     * @throws \VKApiParamPhotoException
     * @throws \VKClientException
     *
     * @return string|null
     */
    private function getImageId($pathImage, $source_id, $flag)
    {
        $id = $this->checkImage($pathImage, $source_id, $flag);

        if (isset($id)) {

            return $id;
        }

        $upload_url = $this->getUploadUrl($flag);
        $request = $this->loadPhoto($upload_url, $pathImage);
        $photo = $this->saveImage($request, $flag);

        if ($photo != false) {
            $id = $this->getImageIdFromResult($photo);

            $this->model_extension_vk_tables->images()->set([
                'type' => $flag == 'album' ? 'album' : 'product',
                DB_PREFIX . 'source_id' => $source_id,
                DB_PREFIX . 'path' => $pathImage,
                'vk_id' => $id
            ]);
        }

        return isset($id) ? (int)$id : false;
    }

    /**
     * Get image id from result
     *
     * @param $photo
     * @return mixed|string|null
     */
    private function getImageIdFromResult($photo)
    {
        if (is_array($photo) && count($photo) == 1) {
            $id = $photo[0]['id'];
        } elseif (is_array($photo) && count($photo) > 1) {
            $id = '';

            foreach ($photo as $item) {
                $id .= $item['id'] . ',';
            }

            $id = rtrim($id, ',');
        }

        return isset($id) ? $id : null;
    }

    /**
     * Check if the picture has been loaded earlier
     *
     * @param $pathImage
     * @param $source_id
     * @param $type
     *
     * @return bool|string
     */
    private function checkImage($pathImage, $source_id, $type)
    {
        $type = $type == 'album' ? $type : 'product';
        $image = $this->model_extension_vk_tables->images()->get($pathImage, $source_id, $type);

        return !empty($image) ? $image['vk_id'] : null;
    }

    /**
     * Get upload url for load image
     *
     * @throws \VKApiException
     * @throws \VKClientException
     *
     * @return mixed
     */
    private function getUploadUrl($flag)
    {
        $upload_url = false;

        switch ($flag) {
            case 'album':
                $upload_url = $this->vkApiClient->photos()->getMarketAlbumUploadServer([
                    'group_id' => (int)ltrim($this->oath['vk_oath_id_group'], '-')
                ]);

                break;
            case 'product_main_photo_id':
                $upload_url = $this->vkApiClient->photos()->getMarketUploadServer([
                    'group_id' => (int)ltrim($this->oath['vk_oath_id_group'], '-'),
                    'main_photo' => 1
                ]);

                break;
            case 'product_photo_ids':
                $upload_url = $this->vkApiClient->photos()->getMarketUploadServer([
                    'group_id' => (int)ltrim($this->oath['vk_oath_id_group'], '-')
                ]);

                break;
        }

        return $upload_url;
    }

    /**
     * Load photo
     *
     * @param $upload_url
     * @param $pathImage
     * @throws \VKApiException
     * @throws \VKClientException
     *
     * * @return array|mixed|null
     */
    private function loadPhoto($upload_url, $pathImage)
    {
        if ($upload_url != false) {
            return $this->vkApiClient->getRequest()->upload(
                $upload_url['upload_url'],
                'photo',
                DIR_IMAGE . $pathImage);
        } else {
            return false;
        }
    }

    /**
     * Save photo for album
     *
     * @param $request
     * @param $flag
     *
     * @return array|bool
     *
     * @throws \VKApiException
     * @throws \VKApiParamHashException
     * @throws \VKApiParamPhotoException
     * @throws \VKClientException
     */
    private function saveImage($request, $flag)
    {
        if (isset($request['error']) || $request == false) {
            return false;
        }

        $response = false;

        switch ($flag) {
            case 'album':
                $response = $this->vkApiClient->photos()->saveMarketAlbumPhoto([
                    'group_id' => (int)ltrim($this->oath['vk_oath_id_group'], '-'),
                    'photo' => $request['photo'],
                    'server' => $request['server'],
                    'hash' => $request['hash']
                ]);
                break;
            case 'product_photo_ids' || 'product_main_photo_id':
                $response = $this->vkApiClient->photos()->saveMarketPhoto([
                    'group_id' => (int)ltrim($this->oath['vk_oath_id_group'], '-'),
                    'photo' => $request['photo'],
                    'server' => $request['server'],
                    'hash' => $request['hash'],
                    'crop_data' => $request['crop_data'],
                    'crop_hash' => $request['crop_hash']
                ]);
                break;
        }

        return $response;
    }

    /**
     * Writing album to the database
     *
     * @param $category
     * @param $album
     */
    private function putTableAlbums($category, $album)
    {
        if (isset($album)) {
            $this->model_extension_vk_tables->albums()->set([
                DB_PREFIX . 'id' => $category['category_id'],
                DB_PREFIX . 'parent_id' => $category['parent_id'],
                'vk_title' => $category['vk_title'],
                'vk_id' => (int)$album['market_album_id']
            ]);
        }
    }

    /**
     * Adding products to a new category or updating products in an old category
     *
     * @param $inputArray
     * @param $flag
     */
    public function addProducts($inputArray, $flag = 'categories')
    {
        $this->load->model('catalog/product');
        $this->load->model('catalog/option');
        $this->load->model('catalog/manufacturer');
        $this->load->model('extension/vk/references');
        $this->load->model('tool/image');

        $offerManufacturers = array();

        $manufacturers = $this->model_catalog_manufacturer->getManufacturers(array());

        foreach ($manufacturers as $manufacturer) {
            $offerManufacturers[$manufacturer['manufacturer_id']] = $manufacturer['name'];
        }

        $shares = $this->model_extension_vk_references->getShares();
        $permalinks = $this->model_extension_vk_references->getPermalinks();

        if ($flag == 'categories') {
            $productsForExport = $this->getAllProducts($inputArray);
            $productsForExport = $this->checkProducts($productsForExport);
        } else {
            $productsForExport = $inputArray;
        }

        $processed = 0;

        foreach ($productsForExport as $key => $product) {     
            $id_main_category = $this->getMainCategory($product['category_id']);
            $link = $this->getLink($permalinks, $product['product_id'], $id_main_category);
            $newPrice = key_exists($product['product_id'], $shares) ? $shares[$product['product_id']]['price'] : null;
            $oldPrice = null;

            $offers = $this->getOffers($product);

            foreach ($offers as $optionsString => $optionsValues) {
                $processed++;
                $optionsString = explode('_', $optionsString);
                $options = array();

                foreach ($optionsString as $optionString) {
                    $option = explode('-', $optionString);
                    $optionIds = explode(':', $option[0]);

                    if ($optionString != '0:0-0') {
                        $optionData = $this->getOptionData($optionIds[1], $option[1]);
                        if (!empty($optionData)) {
                            $options[$optionIds[0]] = array(
                                'name' => $optionData['optionName'],
                                'value' => $optionData['optionValue'],
                                'value_id' => $option[1]
                            );
                        }
                    }
                }

                ksort($options);

                $offerId = array();
                $description = '';

                $quantity = $product['quantity'];
                $status = $product['status'];

                if (isset($newPrice)) {
                    $price = $newPrice;
                    $oldPrice = $product['price'];
                } else {
                    $price = $product['price'];
                }

                foreach($options as $optionKey => $optionData) {
                    $offerId[] = $optionKey.'-'.$optionData['value_id'];
                }

                $offerId = implode('_', $offerId);

                if (!empty($offerId)) {
                    $price = $price + $optionsValues['price'];
                    $oldPrice = isset($oldPrice) ? $oldPrice + $optionsValues['price'] : null;
                    $quantity = $optionsValues['qty'];

                    $description .= 'Option SKU: ' . $offerId . PHP_EOL . PHP_EOL;

                    foreach ($options as $option) {
                        $description .= $option['name'] . ': ' . $option['value'] . PHP_EOL;
                    }

                    $description .= '________________________________________' . PHP_EOL;
                } else {
                    $offerId = 0;
                }

                if (isset($product['description'])) {
//                    $description .= preg_replace("(\<(\/?[^>]+)>)", '', html_entity_decode($product['description'], ENT_QUOTES));
                    $description .= html_entity_decode(strip_tags(html_entity_decode($product['description'])));
                }

                $data = array(
                    'owner_id' => (int)$this->oath['vk_oath_id_group'],
                    'name' => html_entity_decode($product['name']),
                    'description' => !empty($description) ? $description : html_entity_decode($product['name']),
                    //'category_id' => (int)$this->settings['vk_settings_category-conformity'][$id_main_category],
                    'category_id' => 0,
                    'price' => $price,
//                    'deleted' => $quantity > 0 ? 0 : 1,
                    'url' => $link,
                    'dimension_width' => $product['width'] > 0 ? $this->getLength($product['width'], $product['length_class_id']) : null,
                    'dimension_height' => $product['height'] > 0 ? $this->getLength($product['height'], $product['length_class_id']) : null,
                    'dimension_length' => $product['length'] > 0 ? $this->getLength($product['length'], $product['length_class_id']) : null,
                    'weight' => $product['weight'] > 0 ? $this->getWeight($product['weight'], $product['weight_class_id']) : null,
                    'sku' => isset($product['model']) ? $product['model'] : null
                );

                if (!empty($oldPrice)) {
                    $data['old_price'] = $oldPrice;
                }

                if (isset($product['image'])) {
                    //$image_id = (int)$this->getImageId($product['image'], $product['product_id'] . '*' . $offerId, 'product_main_photo_id');
                    $this->model_tool_image->resize($product['image'], 400, 400);
                    $extension = pathinfo($product['image'], PATHINFO_EXTENSION);
                    $image_new = 'cache/' . utf8_substr($product['image'], 0, utf8_strrpos($product['image'], '.')) . '-400x400.' . $extension;
                    $image_id = (int)$this->getImageId($image_new, $product['product_id'] . '*' . $offerId, 'product_main_photo_id');

                    if ($image_id != false) {
                        $data['main_photo_id'] = $image_id;
                    } else {
                        $data['main_photo_id'] = (int)$this->getImageId('catalog/vk/no-photo.png', $product['product_id'] . '*' . $offerId, 'product_main_photo_id');
                    }
                }

                if ($quantity < 1 || $status <> 1) {
                    if (!empty($product['offers_vk']) && key_exists($offerId, $product['offers_vk'])) {
                        $this->deleteProduct($product['offers_vk'][$offerId]['vk_id'], $product['product_id'], $offerId);

                        continue;
                    } else {

                        continue;
                    }
                }

                //Если такого варианта нет в вк, то создаем
                if (empty($product['offers_vk']) || !key_exists($offerId, $product['offers_vk'])) {
                    $result = $this->vkApiClient->market()->add($data);
                    $product_vk_id = isset($result['market_item_id']) ? $result['market_item_id'] : false;

                    if ($product_vk_id != false) {
                        $this->model_extension_vk_tables->products()->set(
                            array(
                                DB_PREFIX . 'id' => $product['product_id'],
                                'vk_id' => $product_vk_id,
                                'categories_albums' => json_encode($product['category_id']),
                                'offer' => $offerId
                            )
                        );
                    }

                //Если есть, то обновляем
                } else {
                    $data['item_id'] = $product['offers_vk'][$offerId]['vk_id'];
                    $this->vkApiClient->market()->edit($data);

                    if (implode(',', $product['category_id']) <> implode(',', $product['old_categories_albums'])){
                        // Удалим товар из старого альбома
                        $product_vk_id = $data['item_id'];

                        $this->vkApiClient->market()->removeFromAlbum(
                            array(
                                'owner_id' => (int)$this->oath['vk_oath_id_group'],
                                'item_id' => $product_vk_id,
                                'album_ids' => implode(',', $product['old_categories_albums'])
                            )
                        );                        
                    } else {// если категория не менялась, то не проставляем $product_vk_id, чтобы потом не было добавления в альбом
                        $product_vk_id = null;
                    }

                    $this->model_extension_vk_tables->products()->edit(
                        array(
                            'vk_id' => $product['offers_vk'][$offerId]['vk_id'],
                            'categories_albums' => json_encode($product['category_id'])
                        )
                    );
                }
// $this->logger->write('album_id = ' . $product['category_id'][$id_main_category], 'error', 'string');
                if (!empty($product_vk_id)) {
                    $this->vkApiClient->market()->addToAlbum(
                        array(
                            'owner_id' => (int)$this->oath['vk_oath_id_group'],
                            'item_id' => $product_vk_id,
                            'album_ids' => implode(',', $product['category_id'])
                        )
                    );
                }

                if ($processed >= self::UPLOAD_PRODUCT_PART_COUNT) {

                    break;
                }
            }

            if ($processed >= self::UPLOAD_PRODUCT_PART_COUNT) {

                break;
            }

            unset($productsForExport[$key]);
        }
        
        if (count($productsForExport) > 0){
            $this->logger->write('VK sync paused. ' . $processed . ' products processed. ' . count($productsForExport) . ' products remains to process', 'error', 'string');
        } else {
            $this->logger->write('VK sync finished. ' . $processed . ' products processed.', 'error', 'string');
        }
        
        file_put_contents(DIR_SYSTEM . '/vk_cron/products_for_export.json', json_encode($productsForExport));
    }

    /**
     * Get all products for export
     *
     * @param $categories
     * @return array
     */
    private function getAllProducts($categories)
    {
        $allProducts = array();

        $this->load->model('catalog/product');
        $last_upload = $this->settings['vk_settings_last_upload'];
        if (!$last_upload) {
            $last_upload = '2000-01-01';
        }
        $this->model_setting_setting->editSettingValue('vk_settings', 'vk_settings_last_upload', $this->model_catalog_product->getMaxLastModified());

        foreach ($categories as $category) {
            $products = $this->model_catalog_product->getModifiedProductsByCategoryHierarchyAndCity($category['category_id'], $category['city_filter'], $last_upload);

            foreach ($products as &$product) {
                if (!key_exists($product['product_id'], $allProducts)) {
                    $product['category_id'] = array(
                        (int)$category['category_id'] => (int)$category['album_id']
                    );
                    $allProducts[$product['product_id']] = $product;
                } else {
                    $allProducts[$product['product_id']]['category_id'][(int)$category['category_id']] = (int)$category['album_id'];
                }
            }

            unset($product);
        }



        return $allProducts;
    }

    /**
     * Checking for the existence of an product
     *
     * @param $productsForExport
     * @param $categoriesForExport
     * @return mixed
     * @throws \VKApiException
     * @throws \VKClientException
     */
    private function checkProducts($productsForExport)
    {
        $productsFromBase = $this->actualBase();

        foreach ($productsFromBase as $oc_id => &$productFromBase) {
            if (!key_exists($oc_id, $productsForExport)) {
                // $this->deleteProducts($productFromBase['offers'], $oc_id);
            } else {
                $productsForExport[$oc_id]['offers_vk'] = $productFromBase['offers'];
                $productsForExport[$oc_id]['old_categories_albums'] = $productFromBase['categories_albums'];
            }
        }

        unset($productFromBase);

        return $productsForExport;
    }

    /**
     * Delete offers from base and vk
     *
     * @param $offers
     * @param $oc_id
     *
     * @throws \VKApiException
     * @throws \VKApiMarketAlbumNotFoundException
     * @throws \VKClientException
     */
    private function deleteProducts($offers, $oc_id)
    {
        foreach ($offers as $offer) {
            $this->vkApiClient->market()->delete([
                'owner_id' => (int)$this->oath['vk_oath_id_group'],
                'item_id' => (int)$offer['vk_id']
            ]);

            $this->model_extension_vk_tables->images()->delete($oc_id . '*' . $offer['offer'], 'product');
        }

        $this->model_extension_vk_tables->products()->delete($oc_id);
    }

    /**
     * Delete offer from base and vk
     *
     * @param $vk_id
     * @param $oc_id
     * @param $offerId
     *
     * @throws \VKApiAccessMarketException
     * @throws \VKApiException
     * @throws \VKClientException
     */
    private function deleteProduct($vk_id, $oc_id, $offerId)
    {
        $this->vkApiClient->market()->delete([
            'owner_id' => (int)$this->oath['vk_oath_id_group'],
            'item_id' => (int)$vk_id
        ]);

        $this->model_extension_vk_tables->images()->delete($oc_id . '*' . $offerId, 'product');
        $this->model_extension_vk_tables->products()->delete($oc_id);
    }

    /**
     * Checking the relevance of the database
     *
     * @param $productsFromBase
     * @param $categoriesForExport
     * @return mixed
     * @throws \VKApiException
     * @throws \VKClientException
     */
    private function actualBase()
    {
        $productsFromBase = $this->model_extension_vk_tables->products()->getAll();
        $productsFromVk = $this->getProductsFromVk();

        if (!empty($productsFromBase) && is_array($productsFromBase)) {
            foreach ($productsFromBase as $oc_product_id => &$product) {

                foreach ($product['offers'] as $offerId => $data) {
                    if (!key_exists($data['vk_id'], $productsFromVk)) {
                        $this->model_extension_vk_tables->products()->delete($data['vk_id'], 'vk_id');
                        unset($product['offers'][$offerId]);
                    }
                }

                if (empty($product['offers'])) {
                    unset($productsFromBase[$oc_product_id]);
                }
            }

            unset($product);
        }

        return $productsFromBase;
    }

    /**
     * Get products from vk album
     *
     * @param $categoriesForExport
     * @return array
     * @throws \VKApiException
     * @throws \VKClientException
     */
    private function getProductsFromVk()
    {
        $productsFromVk = array();

        $offset = 0;

        do {
            $productsIteration = $this->vkApiClient->market()->get([
                'owner_id' => (int)$this->oath['vk_oath_id_group'],
                'count' => 200,
                'offset' => $offset
            ]);

            if (count($productsIteration['items']) > 0) {
                foreach ($productsIteration['items'] as $item) {
                    $productsFromVk[$item['id']] = array(
                        'name' => $item['title'],
                        'offer' => $this->getOfferId($item['description'])
                    );
                }
            }

            $offset = $offset + 200;
        } while (count($productsIteration['items']) > 0);

        return $productsFromVk;
    }

    /**
     * Get offer id
     *
     * @param $description
     *
     * @return string
     */
    public function getOfferId($description)
    {
        if (is_string($description) && stripos($description, '________________________________________')) {
            $description = explode('________________________________________', $description);
            $descriptionOption = explode(PHP_EOL, $description[0]);

            return str_replace('Option SKU: ', '', $descriptionOption[0]);
        } else {

            return 0;
        }
    }

    /**
     * Get once category
     *
     * @param $categories
     * @return int|string
     */
    private function getMainCategory($categories)
    {
        foreach ($categories as $key => $value) {
            $category_id = $key;

            break;
        }

        return $category_id;
    }

    /**
     * Link constructor
     *
     * @param $permalinks
     * @param $product_id
     * @param $category_id
     * @return string|null
     */
    private function getLink($permalinks, $product_id, $category_id)
    {
        if (key_exists($product_id, $permalinks['products'])) {
            return HTTPS_CATALOG . $permalinks['products'][$product_id];
        } else {
            return null;
        }
    }

    /**
     * Get offers
     *
     * @param $product
     *
     * @return array|\string[][]
     */
    public function getOffers($product) {
        // Формируем офферы отнсительно доступных опций
        $options = $this->model_catalog_product->getProductOptions($product['product_id']);
        $offerOptions = array('select', 'radio');
        $requiredOptions = array();
        $notRequiredOptions = array();
        // Оставляем опции связанные с вариациями товаров, сортируем по параметру обязательный или нет
        foreach($options as $option) {
            if(in_array($option['type'], $offerOptions)) {
                if($option['required']) {
                    $requiredOptions[] = $option;
                } else {
                    $notRequiredOptions[] = $option;
                }
            }
        }

        $offers = array();
        // Сначала совмещаем все обязательные опции
        foreach($requiredOptions as $requiredOption) {
            // Если первая итерация
            if(empty($offers)) {
                foreach($requiredOption['product_option_value'] as $optionValue) {
                    $offers[$requiredOption['product_option_id'].':'.$requiredOption['option_id'].'-'.$optionValue['option_value_id']] = array(
                        'price' => (float)$this->getOptionPrice($optionValue),
                        'qty' => $optionValue['quantity']
                    );
                }
            } else {
                foreach($offers as $optionKey => $optionAttr) {
                    unset($offers[$optionKey]); // Работая в контексте обязательных опций не забываем удалять прошлые обязательные опции, т.к. они должны быть скомбинированы с другими обязательными опциями
                    foreach($requiredOption['product_option_value'] as $optionValue) {
                        $offers[$optionKey.'_'.$requiredOption['product_option_id'].':'.$requiredOption['option_id'].'-'.$optionValue['option_value_id']] = array(
                            'price' => $optionAttr['price'] + (float)$this->getOptionPrice($optionValue),
                            'qty' => ($optionAttr['qty'] > $optionValue['quantity']) ?
                                $optionValue['quantity'] : $optionAttr['qty']
                        );
                    }
                }
            }
        }

        // Совмещаем или добавляем необязательные опции, учитывая тот факт что обязательных опций может и не быть.
        foreach($notRequiredOptions as $notRequiredOption) {
            // Если обязательных опцией не оказалось и первая итерация
            if(empty($offers)) {
                $offers['0:0-0'] = 0; // В случае работы с необязательными опциями мы должны учитывать товарное предложение без опций, поэтому создадим "пустую" опцию
                foreach($notRequiredOption['product_option_value'] as $optionValue) {
                    $offers[$notRequiredOption['product_option_id'].':'.$notRequiredOption['option_id'].'-'.$optionValue['option_value_id']] = array(
                        'price' => (float)$this->getOptionPrice($optionValue),
                        'qty' => $optionValue['quantity']
                    );
                }
            } else {
                foreach($offers as $optionKey => $optionAttr) {
                    foreach($notRequiredOption['product_option_value'] as $optionValue) {
                        $offers[$optionKey.'_'.$notRequiredOption['product_option_id'].':'.$notRequiredOption['option_id'].'-'.$optionValue['option_value_id']] = array(
                            'price' => $optionAttr['price'] + (float)$this->getOptionPrice($optionValue),
                            'qty' => ($optionAttr['qty'] > $optionValue['quantity']) ?
                                $optionValue['quantity'] : $optionAttr['qty']
                        );
                    }
                }
            }
        }

        if(empty($offers)) {
            $offers = array('0:0-0' => array('price' => '0', 'qty' => '0'));
        }

        return $offers;
    }

    /**
     * Get option value
     *
     * @param array $optionValue
     *
     * @return float|int|mixed
     */
    private function getOptionPrice($optionValue) {
        if ($optionValue['price_prefix'] === '-') {
            return $optionValue['price'] * -1;
        }

        return $optionValue['price'];
    }

    /**
     * Get option data
     *
     * @param int $optionId
     * @param int $optionValueId
     *
     * @return array
     */
    private function getOptionData($optionId, $optionValueId) {
        if (!empty($this->options[$optionId])) {
            $option = $this->options[$optionId];
        } else {
            $option = $this->model_catalog_option->getOption($optionId);
            $this->options[$optionId] = $option;
        }

        if (!empty($this->optionValues[$optionValueId])) {
            $optionValue = $this->optionValues[$optionValueId];
        } else {
            $optionValue = $this->model_catalog_option->getOptionValue($optionValueId);
            $this->optionValues[$optionValueId] = $optionValue;
        }

        if (!empty($option['name']) || !empty($optionValue['name'])) {
            return array(
                'optionName' => $option['name'],
                'optionValue' => $optionValue['name']
            );
        } else {

            return [];
        }
    }

    /**
     * Get length
     *
     * @param $value
     * @param $class_id
     *
     * @return int
     */
    private function getLength($value, $class_id)
    {
/*        $key = array_search($class_id, $this->settings['vk_settings_length']);

        if ($key !== false) {
            $constLength = $this->model_extension_vk_references->getVkLengthFactor();

            return (int)($value * $constLength[$key]);
        } else {

            return (int)$value;
        }
*/
        return (int)$value;
    }

    /**
     * Get weight
     *
     * @param $value
     * @param $class_id
     *
     * @return int
     */
    private function getWeight($value, $class_id)
    {
/*        $key = array_search($class_id, $this->settings['vk_settings_weight']);

        if ($key !== false) {
            $constWeight = $this->model_extension_vk_references->getVkWeightFactor();

            return (int)($value * $constWeight[$key]);
        } else {

            return (int)$value;
        }
*/
        return (int)$value;
    }

    /**
     * Add product in opencart
     *
     * @param $item
     */
    public function addProduct($item)
    {
        /*

        Закомментировал - не будет синхронизации в эту сторону

        $oc_product_id = $this->model_extension_vk_tables->products()->get($item['id'], 'vk_id', DB_PREFIX . 'id');

        if (empty($oc_product_id)) {
            $oc_category_id = $this->addCategory($item['category']['name']);

            if (isset($item['price']['old_amount']) && $item['price']['old_amount'] != 0) {
                $price = $item['price']['old_amount']/100;
                $oldPrice = $item['price']['amount']/100;
            } else {
                $price = $item['price']['amount']/100;
                $oldPrice = '';
            }

            $data = array (
                'product_description' =>
                    array (
                        1 =>
                            array (
                                'name' => htmlspecialchars($item['title']),
                                'description' => htmlspecialchars($item['description']),
                                'meta_title' => $this->translit(htmlspecialchars($item['title'])),
                                'meta_description' => '',
                                'meta_keyword' => '',
                                'tag' => '',
                            ),
                    ),
                'model' => isset($item['sku']) ? $item['sku'] : 'Модель 1',
                'sku' => isset($item['sku']) ? $item['sku'] : '',
                'upc' => '',
                'ean' => '',
                'jan' => '',
                'isbn' => '',
                'mpn' => '',
                'location' => '',
                'price' => $price,
                'tax_class_id' => '0',
                'quantity' => '1',
                'minimum' => '1',
                'subtract' => '1',
                'stock_status_id' => '7',
                'shipping' => '1',
                'keyword' => $this->translit(htmlspecialchars($item['title'])),
                'date_available' => date('Y-m-d'),
                'length' => '',
                'width' => '',
                'height' => '',
                'length_class_id' => '1',
                'weight' => '',
                'weight_class_id' => '1',
                'status' => '1',
                'sort_order' => '1',
                'manufacturer' => '',
                'manufacturer_id' => '0',
                'category' => '',
                'product_category' =>
                    array (
                        0 => $oc_category_id,
                    ),
                'filter' => '',
                'product_store' =>
                    array (
                        0 => '0',
                    ),
                'download' => '',
                'related' => '',
                'option' => '',
                'product_discount' => array (),
                'product_special' => array (),
                'image' => $this->downloadImage($item['thumb_photo']),
                'points' => '',
                'product_reward' =>
                    array (
                        1 =>
                            array (
                                'points' => '',
                            ),
                    ),
                'product_layout' =>
                    array (
                        0 => '',
                    ),
            );

            if (!empty($oldPrice)) {
                $data['product_special'] = array(
                    0 =>
                        array (
                            'customer_group_id' => '1',
                            'priority' => '',
                            'price' => $oldPrice,
                            'date_start' => '',
                            'date_end' => '',
                        ),
                );
            }

            $oc_product_id = $this->model_catalog_product->addProduct($data);

            $this->model_extension_vk_tables->products()->set(
                array(
                    DB_PREFIX . 'id' => $oc_product_id,
                    'vk_id' => $item['id'],
                    'categories_albums' => json_encode(array($oc_category_id => 0)),
                    'offer' => 0
                )
            );
        }
        */
    }

    /**
     * Add category in opencart
     *
     * @param $title
     *
     * @return int
     */
    private function addCategory($title)
    {
        /*

        Закомментировал - не будет синхронизации в эту сторону

        $oc_categories = $this->model_catalog_category->getCategories(array(
            'filter_name' => $title
        ));

        if (!empty($oc_categories)) {
            foreach ($oc_categories as $oc_category) {
                if (htmlspecialchars_decode($oc_category['name']) == $title) {
                    $oc_category_id = $oc_category['category_id'];
                }
            }
        }

        if (!isset($oc_category_id)) {
            $data = array (
                'category_description' =>
                    array (
                        1 =>
                            array (
                                'name' => $title,
                                'description' => '',
                                'meta_title' => $this->translit($title),
                                'meta_description' => '',
                                'meta_keyword' => '',
                            ),
                    ),
                'path' => '',
                'parent_id' => '0',
                'filter' => '',
                'category_store' =>
                    array (
                        0 => '0',
                    ),
                'keyword' => $this->translit($title),
                'image' => '',
                'column' => '1',
                'sort_order' => '0',
                'status' => '1',
                'category_layout' =>
                    array (
                        0 => '',
                    ),
            );

            $oc_category_id = $this->model_catalog_category->addCategory($data);
        }

        return (int)$oc_category_id;
        */
    }

    /**
     * Transliteration
     *
     * @param $string
     *
     * @return string
     */
    public function translit($string)
    {
        $letters = array(
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I',
            'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH',
            'Ш' => 'SH', 'Щ' => 'CSH', 'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
            'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'csh', 'ь' => '', 'ы' => 'i', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya', ' ' => '_'
        );

        return strtr($string, $letters);
    }

    /**
     * Download image
     *
     * @param $fullLink
     *
     * @return string
     */
    public function downloadImage($fullLink)
    {
        if (!file_exists(DIR_IMAGE . 'catalog/')) {
            mkdir(DIR_IMAGE . 'catalog/');
        }

        if (strripos($fullLink, '?') !== false) {
            $link = explode('?', $fullLink)[0];
        } else {
            $link = $fullLink;
        }

        $array = explode('/', $link);
        $name = end($array);
        $path = $path = DIR_IMAGE . 'catalog/' . $name;

        if (!file_exists($path)) {
            $check = copy($fullLink, $path);
        } else {
            $check = true;
        }

        return $check ? 'catalog/' . $name : '';
    }
}