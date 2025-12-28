<?php

declare(strict_types=1);

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
 *
 * Main class for the Tarifador module. Handles installation, page rendering,
 * AJAX requests, and database interactions for call detail records, rates, and users.
 *
 * @package FreePBX\modules
 */
class Tarifador extends FreePBX_Helpers implements BMO
{
    use RateTrait, CallTrait, PinUserTrait, CelTrait;

    /**
     * @var Database The FreePBX database object.
     */
    private readonly Database $db;

    /**
     * @var int The ID from the request, used for editing or deleting records.
     */
    private readonly int $id;

    /**
     * @var string The current page being displayed.
     */
    private readonly string $page;

    /**
     * @var string The action being performed (e.g., 'add', 'edit', 'delete').
     */
    private readonly string $action;

    /**
     * @var string The view being rendered (e.g., 'form', 'grid').
     */
    private readonly string $view;

    /**
     * @var string The AJAX command being executed.
     */
    private readonly string $command;

    /**
     * @var string JSON data identifier, typically for grid data requests.
     */
    private readonly string $jdata;

    /**
     * Tarifador constructor.
     *
     * @param mixed|null $freepbx The FreePBX main object.
     * @throws Exception If the FreePBX object is not provided.
     */
    public function __construct($freepbx = null)
    {
        if ($freepbx === null) {
            throw new Exception("Not given a FreePBX Object");
        }
        $this->freepbx = $freepbx;
        $this->db = $this->freepbx->Database;
        $this->id = (int)$this->getReq('id', 0);
        $this->page = $this->getReq('page', '');
        $this->action = $this->getReq('action', '');
        $this->view = $this->getReq('view', '');
        $this->command = $this->getReq('command', '');
        $this->jdata = $this->getReq('jdata', '');
    }

    /**
     * Module installation routine.
     * @return void
     */
    public function install(): void
    {
        // TODO: Implement install() method.
    }

    /**
     * Module uninstallation routine.
     * @return void
     */
    public function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }

    /**
     * Handles page actions like adding, editing, or deleting records before the page is rendered.
     * @return void
     */
    public function doConfigPageInit(): void
    {
        match ($this->page) {
            'rate' => match ($this->action) {
                'add' => $this->addRate($_REQUEST),
                'delete' => $this->deleteRate($this->id),
                'edit' => $this->updateRate($_REQUEST),
                default => null
            },
            'pinuser' => match ($this->action) {
                'sync' => $this->syncPinUser(),
                'delete' => $this->deletePinUser($this->id),
                'edit' => $this->updatePinUser($_REQUEST),
                'import' => $this->importPinUser($_REQUEST),
                default => null
            },
            default => null
        };
    }

    /**
     * Generates action bar buttons for form views.
     *
     * @param array $request The request array.
     * @return array An array of buttons to be displayed.
     */
    public function getActionBar(array $request): array
    {
        if (($request['display'] ?? null) !== 'tarifador' || ($request['view'] ?? null) !== 'form') {
            return [];
        }

        $buttons = [
            'reset' => ['name' => 'reset', 'id' => 'reset', 'value' => _("Reset")],
            'submit' => ['name' => 'submit', 'id' => 'submit', 'value' => _("Submit")],
        ];

        if (!empty($request['id'])) {
            $buttons['delete'] = ['name' => 'delete', 'id' => 'delete', 'value' => _('Delete')];
        }

        return $buttons;
    }

    /**
     * Whitelists AJAX commands that this module can handle.
     *
     * @param string $command The AJAX command to check.
     * @param array $setting Reference to the settings array.
     * @return bool True if the command is allowed, false otherwise.
     */
    public function ajaxRequest(string $command, array &$setting): bool
    {
        return match ($command) {
            "getJSON", "updateOrderRate", "getDepartment", "getUser", "getCel", "getDisposition", "getTopSrcCount", "getTopDstCount", "getCallsHour" => true,
            default => false,
        };
    }

    /**
     * Handles incoming AJAX requests and routes them to the appropriate method.
     *
     * @return mixed The result of the AJAX handler method.
     */
    public function ajaxHandler(): mixed
    {
        return match ($this->command) {
            "getJSON" => $this->handleGetJson(),
            "updateOrderRate" => $this->updateOrderRate($_REQUEST),
            "getDepartment" => $this->getDepartment($_REQUEST),
            "getUser" => $this->getUser($_REQUEST),
            "getCel" => $this->getCel($_REQUEST),
            "getDisposition" => $this->getDisposition($_REQUEST),
            "getTopSrcCount" => $this->getTopSrcCount($_REQUEST),
            "getTopDstCount" => $this->getTopDstCount($_REQUEST),
            "getCallsHour" => $this->getCallsHour($_REQUEST),
            default => false,
        };
    }

    /**
     * Loads the right-side navigation panel view.
     *
     * @param array $request The request array.
     * @return string The rendered HTML for the right navigation.
     */
    public function getRightNav(array $request): string
    {
        return load_view(__DIR__ . "/views/rnav.php", []);
    }

    /**
     * Routes the request to the correct page rendering method.
     *
     * @param string $page The page to display.
     * @return string|null The rendered HTML content for the page or null if page not found.
     */
    public function showPage(string $page): ?string
    {
        return match ($page) {
            'call' => $this->callPage(),
            'rate' => $this->ratePage(),
            'pinuser' => $this->pinuserPage(),
            'stats' => $this->statsPage(),
            default => null,
        };
    }

    /**
     * Handles JSON data requests, typically for populating data grids.
     *
     * @return mixed The data to be encoded as JSON.
     */
    private function handleGetJson(): mixed
    {
        if ($this->jdata !== 'grid') {
            return false;
        }

        return match ($this->page) {
            'call' => $this->getListCdr($_REQUEST),
            'rate' => $this->getListRate(),
            'pinuser' => $this->getListPinuser(),
            'stats' => $this->getTotalCalls($_REQUEST),
            default => false,
        };
    }

    /**
     * Renders the 'Call' page.
     *
     * @return string The rendered HTML content.
     */
    private function callPage(): string
    {
        $data = [];
        if ($this->view === 'form' && !empty($this->id)) {
            $data = $this->getOneCall($this->id) ?? [];
        }
        $viewFile = $this->view === 'form' ? 'form' : 'grid';
        $content = load_view(__DIR__ . "/views/call/{$viewFile}.php", $data);

        return load_view(__DIR__ . '/views/default.php', ['content' => $content, 'title' => _("Call List")]);
    }

    /**
     * Renders the 'Rate' page.
     *
     * @return string The rendered HTML content.
     */
    private function ratePage(): string
    {
        $data = [];
        if ($this->view === 'form' && !empty($this->id)) {
            $data = $this->getOneRate($this->id) ?? [];
        }
        $viewFile = $this->view === 'form' ? 'form' : 'grid';
        $content = load_view(__DIR__ . "/views/rate/{$viewFile}.php", $data);

        return load_view(__DIR__ . '/views/default.php', ['content' => $content, 'title' => _("Rate List")]);
    }

    /**
     * Renders the 'PIN User' page.
     *
     * @return string The rendered HTML content.
     */
    private function pinuserPage(): string
    {
        $data = [];
        if ($this->view === 'form' && !empty($this->id)) {
            $data = $this->getOnePinuser($this->id) ?? [];
        }
        $viewFile = $this->view === 'form' ? 'form' : 'grid';
        $content = load_view(__DIR__ . "/views/pinuser/{$viewFile}.php", $data);

        return load_view(__DIR__ . '/views/default.php', ['content' => $content, 'title' => _("User List")]);
    }

    /**
     * Renders the 'Statistics' page.
     *
     * @return string The rendered HTML content.
     */
    private function statsPage(): string
    {
        $content = load_view(__DIR__ . '/views/stats/chart.php');
        return load_view(__DIR__ . '/views/default.php', ['content' => $content, 'title' => _("Statistics")]);
    }
}