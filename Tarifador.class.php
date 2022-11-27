<?php

namespace FreePBX\modules;

use Exception;
use FreePBX\BMO;
use FreePBX\Database;
use FreePBX\FreePBX_Helpers;
use FreePBX\modules\Tarifador\Traits\CallTrait;
use FreePBX\modules\Tarifador\Traits\CelTrait;
use FreePBX\modules\Tarifador\Traits\PinUserTrait;
use FreePBX\modules\Tarifador\Traits\RateTrait;

/**
 * Class Tarifador
 * @package FreePBX\modules
 * @author Mauro <https://github.com/mrpbueno>
 */

class Tarifador extends FreePBX_Helpers implements BMO
{
    use RateTrait, CallTrait, PinUserTrait, CelTrait;

    /** @var BMO */
    private $freepbx = null;
    /** @var  Database*/
    private $db;
    /** @var integer  */
    private $id;
    /** @var string  */
    private $page;
    /** @var string  */
    private $action;
    /** @var string  */
    private $view;
    /** @var string  */
    private $command;
    /** @var string  */
    private $jdata;
    /**
     * Tarifador constructor.
     *
     * @param object $freepbx
     * @throws Exception
     */
    public function __construct($freepbx = null)
    {
        if ($freepbx == null) {
            throw new Exception("Not given a FreePBX Object");
        }
        $this->freepbx = $freepbx;
        $this->db = $freepbx->Database;
        $this->id = $this->getReq('id', '');
        $this->page = $this->getReq('page', '');
        $this->action = $this->getReq('action', '');
        $this->view = $this->getReq('view', '');
        $this->command = $this->getReq('command', '');
        $this->jdata = $this->getReq('jdata', '');
    }

    public function install()
    {
        // TODO: Implement install() method.
    }

    public function uninstall()
    {
        // TODO: Implement uninstall() method.
    }

    /**
     * Processes form submission and pre-page actions.
     *
     * @return mixed
     * @throws Exception
     */
    public function doConfigPageInit()
    {
        switch ($this->page) {
            case 'rate':
                switch ($this->action) {
                    case 'add':
                        return $this->addRate($_REQUEST);
                        break;
                    case 'delete':
                        return $this->deleteRate($this->id);
                        break;
                    case 'edit':
                        $this->updateRate($_REQUEST);
                        break;
                }
                break;
            case 'pinuser':
                switch ($this->action) {
                    case 'sync':
                        return $this->syncPinUser();
                        break;
                    case 'delete':
                        return $this->deletePinUser($this->id);
                        break;
                    case 'edit':
                        $this->updatePinUser($_REQUEST);
                        break;
                    case 'import':
                        $this->importPinUser($_REQUEST);
                        break;
                }
                break;
        }
    }

    /**
     * Adds buttons to the bottom of pages per set conditions
     *
     * @param array $request $_REQUEST
     *
     * @return array
     */
    public function getActionBar($request)
    {
        switch($request['display']) {
            case 'tarifador':
                $buttons = [
                    'delete' => ['name' => 'delete', 'id' => 'delete', 'value' => _('Excluir'),],
                    'reset' => ['name' => 'reset', 'id' => 'reset', 'value' => _("Redefinir"),],
                    'submit' => ['name' => 'submit', 'id' => 'submit', 'value' => _("Enviar"),],
                ];

                if (!isset($request['id']) || trim($request['id']) == '') {
                    unset($buttons['delete']);
                }
                if (empty($request['view']) || $request['view'] != 'form') {
                    $buttons = [];
                }
                break;
        }
        return $buttons;
    }

    /**
     * Returns bool permissions for AJAX commands
     * https://wiki.freepbx.org/x/XoIzAQ
     * @param string $command The ajax command
     * @param array $setting ajax settings for this command typically untouched
     * @return bool
     */
    public function ajaxRequest($command, &$setting) {
        //The ajax request
        switch ($command) {
            case "getJSON":
            case "updateOrderRate":
            case "getDepartment":
            case "getUser":
            case "getCel":
            case "getDisposition":
            case "getTopSrcCount":
            case "getTopDstCount":
            case "getCallsHour":
                return true;
            break;
            default:
                return false;
        }
    }

    /**
     * Handle Ajax request
     *
     * @return array | bool
     */
    public function ajaxHandler()
    {
        switch($this->command) {
            case "getJSON":
                $page = !empty($this->page) ? $this->page : '';
                if ('grid' == $this->jdata) {
                    switch ($page) {
                        case 'call':
                            return $this->getListCdr($_REQUEST);
                            break;
                        case 'rate':
                            return $this->getListRate();
                            break;
                        case 'pinuser':
                            return $this->getListPinuser();
                            break;
                        case 'stats':
                            return $this->getTotalCalls($_REQUEST);
                            break;
                    }
                }
                break;
            case "updateOrderRate":
                return $this->updateOrderRate($_REQUEST);
                break;
            case "getDepartment":
                return $this->getDepartment($_REQUEST);
                break;
            case "getUser":
                return $this->getUser($_REQUEST);
                break;
            case "getCel":
                return $this->getCel($_REQUEST);
                break;
            case "getDisposition":
                return $this->getDisposition($_REQUEST);
                break;
            case "getTopSrcCount":
                return $this->getTopSrcCount($_REQUEST);
                break;
            case "getTopDstCount":
                return $this->getTopDstCount($_REQUEST);
                break;
            case "getCallsHour":
                return $this->getCallsHour($_REQUEST);
                break;
            default:
                return json_encode(['status' => false, 'message' => _("Solicitação Inválida")]);
        }
    }

    /**
     * @param $request $_REQUEST
     * @return string
     */
    public function getRightNav($request)
    {
        return load_view(__DIR__."/views/rnav.php",[]);
    }

    /**
     * This returns html to the main page
     *
     * @param $page
     * @return string html
     */
    public function showPage($page)
    {
        switch ($page) {
            case 'call':
                return $this->callPage();
                break;
            case 'rate':
                return $this->ratePage();
                break;
            case 'pinuser':
                return $this->pinuserPage();
                break;
            case 'stats':
                return $this->statsPage();
                break;
        }
    }

    private function callPage()
    {
        $content = load_view(__DIR__ . '/views/call/grid.php');
        if ('form' == $this->view) {
            $content = load_view(__DIR__ . '/views/call/form.php');
            if (isset($this->id) && !empty($this->id)) {
                $content = load_view(__DIR__.'/views/call/form.php', $this->getOneCall($this->id));
            }
        }
        return load_view(__DIR__.'/views/default.php', ['content' => $content, 'title' => _("Lista de chamadas")]);
    }

    private function ratePage()
    {
        $content = load_view(__DIR__ . '/views/rate/grid.php');
        if ('form' == $this->view) {
            $content = load_view(__DIR__ . '/views/rate/form.php');
            if (isset($this->id) && !empty($this->id)) {
                $content = load_view(__DIR__.'/views/rate/form.php', $this->getOneRate($this->id));
            }
        }
        return load_view(__DIR__.'/views/default.php', ['content' => $content, 'title' => _("Lista de tarifas")]);
    }

    private function pinuserPage()
    {
        $content = load_view(__DIR__ . '/views/pinuser/grid.php');
        if('form' == $this->view){
            $content = load_view(__DIR__ . '/views/pinuser/form.php');
            if(isset($this->id) && !empty($this->id)){
                $content = load_view(__DIR__.'/views/pinuser/form.php', $this->getOnePinuser($this->id));
            }
        }
        return load_view(__DIR__.'/views/default.php', ['content' => $content, 'title' => _("Lista de usuários")]);
    }

    private function statsPage()
    {
        $content = load_view(__DIR__ . '/views/stats/chart.php');
        return load_view(__DIR__.'/views/default.php', ['content' => $content, 'title' => _("Estatísticas")]);
    }
}