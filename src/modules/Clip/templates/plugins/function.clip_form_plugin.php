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
 * Generic Form Plugin.
 * Clip's interface to load one of its form plugins.
 *
 * Available parameters:
 *  - tid       (integer) Pubtype's ID (defaults to current pubtype)
 *  - pid       (integer) Publication ID (defaults to form's publication ID)
 *  - field     (string) Pubtype's field name.
 *  - mandatory (bool) Whether this form field is mandatory.
 *  - maxLength (integer) Maximum lenght of the input (optional).
 *
 * Example:
 *
 *  <samp>{clip_form_plugin field='title' mandatory=true}</samp>
 *
 * @param array            $params All parameters passed to this plugin from the template.
 * @param Zikula_Form_View $view   Reference to the {@link Zikula_Form_View} object.
 *
 * @return mixed Plugin output.
 */
function smarty_function_clip_form_plugin($params, Zikula_Form_View &$render)
{
    if (!isset($params['field']) || !$params['field']) {
        return LogUtil::registerError($render->__f('Error! Missing argument [%s].', 'field'));
    }

    $params['tid'] = isset($params['tid']) && $params['tid'] ? $params['tid'] : (int)$render->eventHandler->getTid();
    $params['pid'] = isset($params['pid']) && $params['pid'] ? $params['pid'] : (int)$render->eventHandler->getId();

    // form framework parameters adjustment
    $params['id'] = "clip{$params['tid']}_{$params['pid']}_{$params['field']}";
    $params['group'] = 'data';

    $field = $render->eventHandler->getPubFieldData($params['field']);

    // read settings in pubfields, if set by template ignore settings in pubfields
    if (!isset($params['mandatory'])) {
        $params['mandatory'] = $field['ismandatory'];
    }
    if (!isset($params['maxLength'])){
        $params['maxLength'] = $field['fieldmaxlength'];
    }

    $plugin = Clip_Util_Plugins::get($field['fieldplugin']);

    if (method_exists($plugin, 'pluginRegister')) {
        return $plugin->pluginRegister($params, $render);
    } else {
        return $render->registerPlugin(get_class($plugin), $params);
    }
}
