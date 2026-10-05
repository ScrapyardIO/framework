<?php

namespace GeneralPurposeIO\Core\Modules;

use GeneralPurposeIO\Core\Host;
use GeneralPurposeIO\Core\Modules\Nodes\ComposerFinderNode;
use GeneralPurposeIO\Core\Modules\Nodes\ModuleInstallNode;
use GeneralPurposeIO\Core\Modules\Nodes\ModuleSelectNode;
use GeneralPurposeIO\Core\Modules\Nodes\ModuleStateNode;
use Voyager\Workflows\Flow;

/**
 * Installs the microscrap packages this machine can run into the application, with Composer run by the PHP binary
 * running the command, so Composer checks ext-posi and ext-ftdi against that binary.
 *
 * Reads from the bag: `interactive` (bool), `only` (a module's short name, or unset for the list), `base_path` (the
 * application's root), `output` (a callable given what Composer prints when it does not have the terminal). Writes
 * `module_results` (short name => outcome), or `modules_note` when it ends without installing.
 *
 * Every transition has a name. A node stops the flow by returning null, and no node here has a default successor.
 */
class ModulesFlow extends Flow
{
    public function __construct(Host $host = new Host)
    {
        $state = new ModuleStateNode($host);
        $composer = new ComposerFinderNode($host);
        $select = new ModuleSelectNode;
        $install = new ModuleInstallNode($host);

        $state->next($composer, 'find-composer');
        $composer->next($select, 'select-modules');
        $select->next($install, 'install-modules');

        parent::__construct($state);
    }
}
