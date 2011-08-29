<?php
/**
 * Clip
 *
 * @copyright  (c) Clip Team
 * @link       http://code.zikula.org/clip/
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package    Clip
 * @subpackage View_Plugins
 */

/**
 * Form Plugin to handle a pubtype's relation Autocompleter.
 *
 * @param array            $params All parameters passed to this plugin from the template.
 * @param Zikula_Form_View $render Reference to the {@link Zikula_Form_View} object.
 *
 * @return mixed False on failure, or the HTML output.
 */
function smarty_function_clip_form_relation($params, Zikula_Form_View &$render)
{
    if (!isset($params['alias']) || !$params['alias']) {
        return LogUtil::registerError($render->__f('Error! Missing argument [%s].', 'alias'));
    }

    $params['tid'] = isset($params['tid']) && $params['tid'] ? $params['tid'] : (int)$render->eventHandler->getTid();
    $params['pid'] = isset($params['pid']) && $params['pid'] ? $params['pid'] : (int)$render->eventHandler->getId();

    // form framework parameters adjustment
    $params['id'] = "cliprel{$params['tid']}_{$params['pid']}_{$params['alias']}";
    $params['group'] = 'data';

    $classname = isset($params['classname']) && class_exists($params['classname']) ? $params['classname'] : 'Clip_Form_Relation';
    unset($params['classname']);

    return $render->registerPlugin($classname, $params);
}
