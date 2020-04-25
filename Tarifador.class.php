<?php

namespace FreePBX\modules;

use Exception;
use FreePBX\BMO;
use FreePBX\FreePBX_Helpers;
use FreePBX\modules\Tarifador\Traits\CallTrait;
use FreePBX\modules\Tarifador\Traits\PinUserTrait;
use FreePBX\modules\Tarifador\Traits\RateTrait;

class Tarifador extends FreePBX_Helpers implements BMO
{
    use RateTrait, CallTrait, PinUserTrait;

    /** @var BMO */
    private $FreePBX = null;
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
        $this->FreePBX = $freepbx;
        $this->db = $freepbx->Database;
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
     * @param string $page Display name
     * @return bool
     * @throws Exception
     */
    public function doConfigPageInit($page)
    {
        $action = $this->getReq('action', '');
        $id = $this->getReq('id', '');
        $page = $this->getReq('page', '');

        switch ($page) {
            case 'rate':
                switch ($action) {
                    case 'add':
                        return $this->addRate($_REQUEST);
                        break;
                    case 'delete':
                        return $this->deleteRate($id);
                        break;
                    case 'edit':
                        $this->updateRate($_REQUEST);
                        break;
                }
            case 'pinuser':
                switch ($action) {
                    case 'sync':
                        return $this->syncPinuser();
                        break;
                    case 'delete':
                        return $this->deletePinuser($id);
                        break;
                    case 'edit':
                        $this->updatePinuser($_REQUEST);
                        break;
                    case 'import':
                        $this->importPinuser($_REQUEST);
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
        switch($_REQUEST['command']) {
            case "getJSON":
                $page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : '';
                if ('grid' == $_REQUEST['jdata']) {
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
        return load_view(__DIR__."/views/rnav.php",array());
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
                $content = load_view(__DIR__ . '/views/call/grid.php');
                if ('form' == $_REQUEST['view']) {
                    $content = load_view(__DIR__ . '/views/call/form.php');
                    if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
                        $content = load_view(__DIR__.'/views/call/form.php', $this->getOneCall($_REQUEST['id']));
                    }
                }
                return load_view(__DIR__.'/views/default.php', ['content' => $content]);
                break;
            case 'rate':
                $content = load_view(__DIR__ . '/views/rate/grid.php');
                if ('form' == $_REQUEST['view']) {
                    $content = load_view(__DIR__ . '/views/rate/form.php');
                    if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
                        $content = load_view(__DIR__.'/views/rate/form.php', $this->getOneRate($_REQUEST['id']));
                    }
                }
                return load_view(__DIR__.'/views/default.php', ['content' => $content]);
                break;
            case 'pinuser':
                $content = load_view(__DIR__ . '/views/pinuser/grid.php');
                if('form' == $_REQUEST['view']){
                    $content = load_view(__DIR__ . '/views/pinuser/form.php');
                    if(isset($_REQUEST['id']) && !empty($_REQUEST['id'])){
                        $content = load_view(__DIR__.'/views/pinuser/form.php', $this->getOnePinuser($_REQUEST['id']));
                    }
                }
                return load_view(__DIR__.'/views/default.php', ['content' => $content]);
                break;
        }
    }
}