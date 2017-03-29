<?php
namespace Reverb;

class ReverbCategories extends ReverbClient
{

    CONST REVERB_CATEGORIES_ENDPOINT = 'categories';
    CONST REVERB_ROOT_KEY = 'categories';

    public function __construct($module)
    {
        parent::__construct($module);
        $this->setEndPoint(self::REVERB_CATEGORIES_ENDPOINT)
            ->setRootKey(self::REVERB_ROOT_KEY);
    }

    /**
     * Get all categories or one by uuid
     *
     * @param null $uuid
     * @return array
     */
    public function getCategories($uuid = null)
    {
        return $this->getListFromEndpoint($uuid);
    }

    /**
     * Return formatted categories for mapping
     */
    public function getFormattedCategories()
    {
        $categories = $this->getCategories();

        $formattedCategories = array();

        foreach ($categories as $category) {
            $formattedCategories[$category['uuid']] = $category['name'];
            $formattedCategories = array_merge($formattedCategories, $this->getSubCategories($category));
        }

        return $formattedCategories;
    }

    /**
     * Return sub categories
     * @param array $category
     * @return array
     */
    private function getSubCategories(array $category)
    {
        $subCategories = array();
        if (isset($category['subcategories'])) {
            foreach ($category['subcategories'] as $subCategory) {
                $subCategories[$subCategory['uuid']] = $subCategory['full_name'];
            }
        }
        return $subCategories;
    }
}