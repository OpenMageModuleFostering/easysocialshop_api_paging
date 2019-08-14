<?php

/**
 * Rewrites original Mage_Catalog_Model_Product_Api to limit result set.
 * 
 * @category    EasySocialShop
 * @package     EasySocialShop_Catalog
 * @author      Alexey Bass (@alexey_bass) 
 * @see Mage_Catalog_Model_Product_Api::items
 */
class EasySocialShop_Catalog_Model_Product_Api extends Mage_Catalog_Model_Product_Api
{
    /**
     * Retrieve list of products with basic info (id, sku, type, set, name)
     *
     * @param array $filters
     * @param string|int $store
     * @return array
     */
    public function items($filters = null, $store = null)
    {
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter($this->_getStoreId($store))
            ->addAttributeToSelect('name');
        
        # defaults for paging
        $paging = array(
            'start' =>  1,
            'size'  => -1,
        );
        
        if (is_array($filters)) {
            
            # cut our custom paging markers from Mage filter so it will not fail
            if (isset($filters['page-start'])) {
                if (is_numeric($filters['page-start'])) {
                    $paging['start'] = ($filters['page-start'] > 0) ? (int) $filters['page-start'] : 1;
                }
                unset($filters['page-start']);
            }
            if (isset($filters['page-size'])) {
                if (is_numeric($filters['page-size'])) {
                    $paging['size'] = ($filters['page-size'] >= 0) ? (int) $filters['page-size']   : -1;
                }
                unset($filters['page-size']);
            }
            
            try {
                foreach ($filters as $field => $value) {
                    if (isset($this->_filtersMap[$field])) {
                        $field = $this->_filtersMap[$field];
                    }

                    $collection->addFieldToFilter($field, $value);
                }
            } catch (Mage_Core_Exception $e) {
                $this->_fault('filters_invalid', $e->getMessage());
            }
        }

        # if size is not set, size is all collection
        if ($paging['size'] === -1) {
            $paging['size'] = count($collection);
        }
        
        $result = array();

        $i = -1;
        $startI = ($paging['start'] - 1) * $paging['size'];
        $endI   = ($paging['size'] > 1) ? ($paging['start'] * $paging['size'] - 1) : $startI;
        foreach ($collection as $product) {
            $i++;
            if ($i > $endI) {
                break;
            }
            if ($i < $startI) {
                continue;
            }
            
//            $result[] = $product->getData();
            $result[] = array( // Basic product data
                'product_id' => $product->getId(),
                'sku'        => $product->getSku(),
                'name'       => $product->getName(),
                'set'        => $product->getAttributeSetId(),
                'type'       => $product->getTypeId(),
                'category_ids'       => $product->getCategoryIds()
            );
        }

        return $result;
    }
}
