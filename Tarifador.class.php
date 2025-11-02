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

class Tarifador extends FreePBX_Helpers implements BMO
{
    use RateTrait, CallTrait, PinUserTrait, CelTrait;

    private readonly Database $db;
    private readonly int $id;
    private readonly string $page;
    private readonly string $action;
    private readonly string $view;
    private readonly string $command;
    private readonly string $jdata;

    public function __construct(private readonly ?BMO $freepbx = null)
    {
        if ($this->freepbx === null) {
            throw new Exception("Not given a FreePBX Object");
        }
        $this->db = $this->freepbx->Database;
        $this->id = (int)$this->getReq('id', 0);
        $this->page = $this->getReq('page', '');
        $this->action = $this->getReq('action', '');
        $this->view = $this->getReq('view', '');
        $this->command = $this->getReq('command', '');
        $this->jdata = $this->getReq('jdata', '');
    }

    public function install(): void
    {
        // TODO: Implement install() method.
    }

    public function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }

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

    public function getActionBar(array $request): array
    {
        if (($request['display'] ?? null) !== 'tarifador' || ($request['view'] ?? null) !== 'form') {
            return [];
        }

        $buttons = [
            'reset' => ['name' => 'reset', 'id' => 'reset', 'value' => _("Redefinir")],
            'submit' => ['name' => 'submit', 'id' => 'submit', 'value' => _("Enviar")],
        ];

        if (!empty($request['id'])) {
            $buttons['delete'] = ['name' => 'delete', 'id' => 'delete', 'value' => _('Excluir')];
        }

        return $buttons;
    }

    public function ajaxRequest(string $command, array &$setting): bool
    {
        return match ($command) {
            "getJSON", "updateOrderRate", "getDepartment", "getUser", "getCel", "getDisposition", "getTopSrcCount", "getTopDstCount", "getCallsHour" => true,
            default => false,
        };
    }

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

    public function getRightNav(array $request): string
    {
        return load_view(__DIR__ . "/views/rnav.php", []);
    }

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

    private function callPage(): string
    {
        $data = [];
        if ($this->view === 'form' && !empty($this->id)) {
            $data = $this->getOneCall($this->id) ?? [];
        }
        $viewFile = $this->view === 'form' ? 'form' : 'grid';
        $content = load_view(__DIR__ . "/views/call/{$viewFile}.php", $data);

        return load_view(__DIR__ . '/views/default.php', ['content' => $content, 'title' => _("Lista de chamadas")]);
    }

    private function ratePage(): string
    {
        $data = [];
        if ($this->view === 'form' && !empty($this->id)) {
            $data = $this->getOneRate($this->id) ?? [];
        }
        $viewFile = $this->view === 'form' ? 'form' : 'grid';
        $content = load_view(__DIR__ . "/views/rate/{$viewFile}.php", $data);

        return load_view(__DIR__ . '/views/default.php', ['content' => $content, 'title' => _("Lista de tarifas")]);
    }

    private function pinuserPage(): string
    {
        $data = [];
        if ($this->view === 'form' && !empty($this->id)) {
            $data = $this->getOnePinuser($this->id) ?? [];
        }
        $viewFile = $this->view === 'form' ? 'form' : 'grid';
        $content = load_view(__DIR__ . "/views/pinuser/{$viewFile}.php", $data);

        return load_view(__DIR__ . '/views/default.php', ['content' => $content, 'title' => _("Lista de usuários")]);
    }

    private function statsPage(): string
    {
        $content = load_view(__DIR__ . '/views/stats/chart.php');
        return load_view(__DIR__ . '/views/default.php', ['content' => $content, 'title' => _("Estatísticas")]);
    }
}