<?php
/**
 * PageMaster
 *
 * @copyright   (c) PageMaster Team
 * @link        http://code.zikula.org/pagemaster/
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @version     $ Id $
 * @package     Zikula_3rdParty_Modules
 * @subpackage  pagemaster
 */

class PageMaster_Form_Plugin_Url extends Form_Plugin_TextInput
{
    public $pluginTitle;
    public $columnDef = 'C(512)';

    function setup()
    {
        $dom = ZLanguage::getModuleDomain('PageMaster');
        $this->setDomain($dom);

        //! field type name
        $this->pluginTitle = $this->__('URL');
    }

    function getFilename()
    {
        return __FILE__;
    }

    function create($view, &$params)
    {
        $this->maxLength = 2000;

        parent::create($view, $params);

        $this->cssClass .= ' url';
    }

    function postRead($data, $field)
    {
        // if there's an URL, process it
        if (!empty($data)) {
            $data = $this->parseURL($data);
        }

        return $data;
    }

    /**
     * Overrides the validation check to allow
     * {modname:func&param=value:type}
     */
    function validate($view)
    {
        parent::validate($view);

        if (!$this->isValid) {
            return;
        }

        if (!empty($this->text)) {
            if (!System::varValidate($this->text, 'url')) {
                if (!$this->parseURL($this->text)) {
                    $this->setError(__('Error! Invalid URL.'));
                }
            }
        }
    }

    /**
     * Method to parse an internal URL 
     */
    function parseURL($url)
    {
        // parse the URL
        // {modname:function&param=value:type}
        if (strpos($url, '{') === 0 && strpos($url, '}') === strlen($url)-1) {
            $url = substr($url, 1, -1);
            $url = explode(':', $url);

            // call[0] should be the module name
            if (isset($url[0]) && !empty($url[0])) { 
                $modname = $url[0];
                // default for params
                $params = array();
                // call[1] can be a function or function&param=value
                if (isset($url[1]) && !empty($url[1])) {
                    $urlparts = explode('&', $url[1]); 
                    $func = $urlparts[0];
                    unset($urlparts[0]);
                    if (count($urlparts) > 0) {
                        foreach ($urlparts as $urlpart) {
                            $part = explode('=', $urlpart);
                            $params[trim($part[0])] = trim($part[1]);
                        }
                    }
                } else {
                    $func = 'main';
                } 
                // addon: call[2] can be the type parameter, default 'user'
                $type = (isset($url[2]) &&!empty($url[2])) ? $url[2] : 'user';

                return ModUtil::url($modname, $type, $func, $params, null, null, true);
            }
        }

        return $url;
    }
}
