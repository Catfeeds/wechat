<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/22
 * Time: 16:05
 */
if($op == 'display'){
    $categoryList = getCategoryTreeArray(OtoModel::getGoodsCategoryList(CATEGORY_SHOW_ALL,'',0,0,15,EXPORT_YES));
}
include $this->template('category');