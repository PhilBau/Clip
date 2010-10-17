<?php
/**
 * Clip
 *
 * @copyright  (c) Clip Team
 * @link       http://code.zikula.org/clip/
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package    Clip
 * @subpackage Form_Plugin
 */

class Clip_Form_Plugin_MultiCheck extends Form_Plugin_CategoryCheckboxList
{
    public $pluginTitle;
    public $columnDef   = 'C(512)';
    public $filterClass = 'ClipMultiList';

    public $config;

    function setup()
    {
        $dom = ZLanguage::getModuleDomain('Clip');
        $this->setDomain($dom);

        //! field type name
        $this->pluginTitle = $this->__('MultiCheckbox List');
    }

    function getFilename()
    {
        return __FILE__;
    }

    static function postRead($data, $field)
    {
        // this plugin return an array by default
        $cat_arr = array();

        if (!empty($data) && $data <> '::') {
            $lang = ZLanguage::getLanguageCode();

            if (strpos($data, ':') === 0) {
                $data = substr($data, 1, -1);
            }

            $catIds = explode(':', $data);
            if (!empty($catIds)) {
                ModUtil::dbInfoLoad('Categories');

                $tables = DBUtil::getTables();

                $category_column = $tables['categories_category_column'];

                $where = array();
                foreach ($catIds as $catId) {
                    $where[] = $category_column['id'].' = \''.DataUtil::formatForStore($catId).'\'';
                }

                $cat_arr = CategoryUtil::getCategories(implode(' OR ', $where), '', 'id');
                foreach ($catIds as $catId) {
                    $cat_arr[$catId]['fullTitle'] = (isset($cat_arr[$catId]['display_name'][$lang]) ? $cat_arr[$catId]['display_name'][$lang] : $cat_arr[$catId]['name']);
                }
            }
        }

        return $cat_arr;
    }

    function render($view)
    {
        return parent::render($view);
    }

    function create($view, &$params)
    {
        $this->saveAsString = 1;

        parent::create($view, $params);
    }

    function load($view, &$params)
    {
        if ($view->eventHandler->getPubfieldData($this->id)) {
            $this->parseConfig($view->eventHandler->getPubfieldData($this->id, 'typedata'));
            $params['category'] = $this->config['category'];
        }

        parent::load($view, $params);

        if ($this->mandatory) {
            array_shift($this->items); //CategorySelector makes a "- - -" entry for mandatory field, what makes no sense for checkboxes
        }
    }

    static function getSaveTypeDataFunc($field)
    {
        $saveTypeDataFunc = 'function saveTypeData()
                             {
                                 $(\'typedata\').value = $F(\'clipplugin_checklist\') ;
                                 closeTypeData();
                             }';

        return $saveTypeDataFunc;
    }

    function getTypeHtml($field, $view)
    {
        $typedata = isset($view->_tpl_vars['typedata']) ? $view->_tpl_vars['typedata'] : 30;
        $this->parseConfig($typedata);

        $registered = CategoryRegistryUtil::getRegisteredModuleCategories('Clip', 'clip_pubtypes');

        $html = ' <div class="z-formrow">
                      <label for="clipplugin_checklist">'.$this->__('Category').':</label>
                      <select id="clipplugin_checklist" name="clipplugin_checklist">';

        $lang = ZLanguage::getLanguageCode();

        foreach ($registered as $property => $catID) {
            $cat = CategoryUtil::getCategoryByID($catID);
            $cat['fullTitle'] = isset($cat['display_name'][$lang]) ? $cat['display_name'][$lang] : $cat['name'];
            $selectedText     = ($this->config['category'] == $catID) ? ' selected="selected"' : '';

            $html .= "    <option{$selectedText} value=\"{$cat['id']}\">{$cat['fullTitle']} [{$property}]</option>";
        }

        $html .= '   </select>
                  </div>';

        return $html;
    }

    /**
     * Parse configuration
     */
    function parseConfig($typedata='', $args=array())
    {
        $this->config = array();

        $this->config['category'] = (int)$typedata;
    }
}