<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2014, Ivan Enderlin. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Hoa\Devtools\Bin;

use Hoa\Console;

/**
 * Class \Hoa\Devtools\Bin\Requiresnapshot.
 *
 * Check if a library requires a new snapshot or not.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2014 Ivan Enderlin.
 * @license    New BSD License
 */

class Requiresnapshot extends Console\Dispatcher\Kit {

    /**
     * Options description.
     *
     * @var \Hoa\Devtools\Bin\Requiresnapshot array
     */
    protected $options = [
        ['no-verbose', Console\GetOption::NO_ARGUMENT, 'V'],
        ['help',       Console\GetOption::NO_ARGUMENT, 'h'],
        ['help',       Console\GetOption::NO_ARGUMENT, '?']
    ];



    /**
     * The entry method.
     *
     * @access  public
     * @return  int
     */
    public function main ( ) {

        $library = null;
        $verbose = Console::isDirect(STDOUT);

        while(false !== $c = $this->getOption($v)) switch($c) {

            case '__ambiguous':
                $this->resolveOptionAmbiguity($v);
              break;

            case 'V':
                $verbose = false;
              break;

            case 'h':
            case '?':
            default:
                return $this->usage();
              break;
        }

        $this->parser->listInputs($library);

        if(empty($library))
            return $this->usage();

        $library = ucfirst(strtolower($library));
        $path    = resolve('hoa://Library/' . $library);

        if(false === file_exists($path))
            throw new Console\Exception(
                'The %s library does not exist.',
                0, $library);

        $tag = Console\Processus::execute(
            'git --git-dir=' . $path . '/.git ' .
                'tag | tail -n 1',
            false
        );

        if(empty($tag))
            throw new Console\Exception('No tag.', 1);

        $timeZone     = new \DateTimeZone('UTC');
        $snapshot     = \DateTime::createFromFormat(
            '*.y.m.d',
            $tag,
            $timeZone
        );
        $sixWeeks     = new \DateInterval('P6W');
        $nextSnapshot = clone $snapshot;
        $nextSnapshot->add($sixWeeks);
        $today        = new \DateTime('now', $timeZone);

        $needNewSnaphot = '+' === $nextSnapshot->diff($today)->format('%R');

        if(true === $needNewSnaphot) {

            if(true === $verbose)
                echo 'A snapshot is required, since ',
                     $nextSnapshot->diff($today)->format('%a'),
                     ' days ';

            $numberOfCommits = (int) Console\Processus::execute(
                'git --git-dir=' . $path . '/.git ' .
                    'rev-list ' . $tag . '..origin/master --count'
            );

            if(true === $verbose)
                echo '(', $numberOfCommits, ' commit',
                     (1 < $numberOfCommits ? 's' : ''),
                     ' to publish)!';

            if(0 < $numberOfCommits)
                $needNewSnaphot = true;
        }
        elseif(true === $verbose)
            echo 'No snapshot is required.', "\n";

        return !$needNewSnaphot;
    }

    /**
     * The command usage.
     *
     * @access  public
     * @return  int
     */
    public function usage ( ) {

        echo 'Usage   : devtools:requiresnapshot <options> library', "\n",
             'Options :', "\n",
             $this->makeUsageOptionsList([
                 'V'    => 'No-verbose, i.e. be as quiet as possible, just ' .
                           'print essential informations.',
                 'help' => 'This help.'
             ]), "\n";

        return;
    }
}

__halt_compiler();
Check if a library requires a new snapshot or not.