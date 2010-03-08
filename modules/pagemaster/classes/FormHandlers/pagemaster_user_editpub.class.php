<?php
/**
 * PageMaster
 *
 * @copyright   (c) PageMaster Team
 * @link        http://code.zikula.org/pagemaster/
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package     Zikula_3rdParty_Modules
 * @subpackage  pagemaster
 */

/**
 * pnForm handler for updating pubdata tables.
 *
 * @author kundi
 */
class pagemaster_user_editpub
{
    var $id;
    var $core_pid;
    var $core_author;
    var $core_hitcount;
    var $core_revision;
    var $core_language;
    var $core_online;
    var $core_indepot;
    var $core_showinmenu;
    var $core_showinlist;
    var $core_publishdate;
    var $core_expiredate;

    var $tid;
    var $tablename;
    var $pubtype;
    var $pubfields;
    var $goto;
    
    function initialize(&$render)
    {
        $dom = ZLanguage::getModuleDomain('pagemaster');

        // process the input parameters
        $this->tid  = (isset($this->pubtype['tid']) && $this->pubtype['tid'] > 0) ? $this->pubtype['tid'] : FormUtil::getPassedValue('tid');
        $this->goto = FormUtil::getPassedValue('goto', '');

        // initialize the publication array
        $pubdata = array();

        // process a new or existing pub, and it's available actions
        if (!empty($this->id)) {
            $pubdata = DBUtil::selectObjectByID($this->tablename, $this->id, 'id');

            $this->pubAssign($pubdata);

            Loader::LoadClass('PmWorkflowUtil', 'modules/pagemaster/classes');
            $actions = PmWorkflowUtil::getActionsForObject($pubdata, $this->tablename, 'id', 'pagemaster');

        } else {
            // initial values
            $this->pubDefault();

            $actions = WorkflowUtil::getActionsByStateArray(str_replace('.xml', '', $this->pubtype['workflow']), 'pagemaster');
        }

        // if there are no actions the user is not allowed to change / submit / delete something.
        // We will redirect the user to the overview page
        if (count($actions) < 1) {
            LogUtil::registerError(__('No workflow actions found. This can be a permissions issue.', $dom));

            return $render->pnFormRedirect(pnModURL('pagemaster', 'user', 'main', array('tid' => $tid)));
        }

        // check for set_* default values
        $fieldnames = array_keys($this->pubfields);

        foreach ($fieldnames as $fieldname)
        {
            $val = FormUtil::getPassedValue('set_'.$fieldname, '');
            if (!empty($val)) {
                $pubdata[$fieldname] = $val;
            }
        }

        // add the pub information to the render if exists
        if (count($pubdata) > 0) {
            $render->assign($pubdata);
        }

        $render->assign('actions', $actions);
        return true;
    }

    function handleCommand(&$render, &$args)
    {
        if (!$render->pnFormIsValid()) {
            return false;
        }

        $data = $render->pnFormGetValues();

        // restore the core values
        $this->pubExtract($data);

        // perform the command
        $data = pnModAPIFunc('pagemaster', 'user', 'editPub',
                             array('data'        => $data,
                                   'commandName' => $args['commandName'],
                                   'pubfields'   => $this->pubfields,
                                   'schema'      => str_replace('.xml', '', $this->pubtype['workflow'])));

        // see http://www.smarty.net/manual/en/caching.groups.php
        $pnr = pnRender::getInstance('pagemaster');
        // clear the view of the current publication
        $pnr->clear_cache(null, 'viewpub'.$this->tid.'|'.$this->core_pid);
        // clear all page of publist
        $pnr->clear_cache(null, 'publist'.$this->tid);
        unset($pnr);

        // check the referer (redirect to admin list)
        // if the item moved to the depot or was deleted
        if ($data['core_indepot'] == 1 || isset($data['deletePub'][$data['id']])) {
            $this->goto = pnModURL('pagemaster', 'user', 'main',
                                   array('tid' => $data['tid']));

        } elseif ($this->goto == 'stepmode') {
            // stepmode can be used to go automaticaly from one workflowstep to the next
            $this->goto = pnModURL('pagemaster', 'user', 'pubedit',
                                   array('tid'  => $data['tid'],
                                         'id'   => $data['id'],
                                         'goto' => 'stepmode'));

        } elseif ($this->goto == 'pubeditlist') {
            $this->goto = pnModURL('pagemaster', 'admin', 'pubeditlist',
                                   array('_id' => $data['tid'] . '_' . $data['core_pid']));

        } elseif (empty($this->goto)) {
            $this->goto = pnModURL('pagemaster', 'user', 'viewpub',
                                   array('tid' => $data['tid'],
                                         'pid' => $data['core_pid']));
        }

        if (empty($data)) {
            return false;
        }

        return $render->pnFormRedirect($this->goto);
    }

    /**
     * Publication data handlers
     */
    function pubDefault()
    {
        $this->core_pid         = NULL;
        $this->core_author      = pnUserGetVar('uid');
        $this->core_hitcount    = 0;
        $this->core_revision    = 0;
        $this->core_language    = '';
        $this->core_online      = 0;
        $this->core_indepot     = 0;
        $this->core_showinmenu  = 0;
        $this->core_showinlist  = 1;
        $this->core_publishdate = NULL;
        $this->core_expiredate  = NULL;
    }

    function pubAssign($pubdata)
    {
        $this->core_pid         = $pubdata['core_pid'];
        $this->core_author      = $pubdata['core_author'];
        $this->core_hitcount    = $pubdata['core_hitcount'];
        $this->core_revision    = $pubdata['core_revision'];
        $this->core_language    = $pubdata['core_language'];
        $this->core_online      = $pubdata['core_online'];
        $this->core_indepot     = $pubdata['core_indepot'];
        $this->core_showinmenu  = $pubdata['core_showinmenu'];
        $this->core_showinlist  = $pubdata['core_showinlist'];
        $this->core_publishdate = $pubdata['core_publishdate'];
        $this->core_expiredate  = $pubdata['core_expiredate'];
    }

    function pubExtract(&$data)
    {
        $data['tid']              = $this->tid;
        $data['id']               = $this->id;
        $data['core_pid']         = $this->core_pid;
        $data['core_author']      = $this->core_author;
        $data['core_revision']    = $this->core_revision;
        $data['core_hitcount']    = $this->core_hitcount;
        $data['core_language']    = isset($data['core_language']) ? $data['core_language'] : $this->core_language;
        $data['core_online']      = $this->core_online;
        $data['core_indepot']     = $this->core_indepot;
        $data['core_showinmenu']  = isset($data['core_showinmenu']) ? $data['core_showinmenu'] : $this->core_showinmenu;
        $data['core_showinlist']  = isset($data['core_showinlist']) ? $data['core_showinlist'] : $this->core_showinlist;
        $data['core_publishdate'] = isset($data['core_publishdate']) ? $data['core_publishdate'] : $this->core_publishdate;
        $data['core_expiredate']  = isset($data['core_expiredate']) ? $data['core_expiredate'] : $this->core_expiredate;
    }
}
