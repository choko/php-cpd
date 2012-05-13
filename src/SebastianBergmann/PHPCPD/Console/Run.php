<?php
/**
 * phpcpd
 *
 * Copyright (c) 2009-2012, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package   phpcpd
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @since     File available since Release 1.0.0
 */

namespace SebastianBergmann\PHPCPD\Console
{
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use SebastianBergmann\FinderFacade\FinderFacade;
    use SebastianBergmann\PHPCPD\Detector\Detector;
    use SebastianBergmann\PHPCPD\Detector\Strategy\DefaultStrategy;
    use SebastianBergmann\PHPCPD\Log\PMD;


    /**
     * TextUI frontend for PHPCPD.
     *
     * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
     * @copyright 2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
     * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
     * @version   Release: @package_version@
     * @link      http://github.com/sebastianbergmann/phpcpd/tree
     * @since     Class available since Release 1.0.0
     */
    class Run extends Command
    {
        public function configure()
        {
          $this
            ->setName('run')
            ->setDescription('Run php copy/paste detector on a source tree.')
            ->addArgument('path', InputArgument::REQUIRED, 'File or path to run CPD on.')
            ->addOption('exclude', null, InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, '', array())
            ->addOption('log-pmd', null, InputOption::VALUE_NONE)
            ->addOption('min-lines', null, InputOption::VALUE_OPTIONAL, '', 5)
            ->addOption('min-tokens', null, InputOption::VALUE_OPTIONAL, '', 70)
            ->addOption('names', null, InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, '', array('*.php'))
            ->addOption('quiet', 'q', InputOption::VALUE_NONE)
            ->addOption('version', 'V', InputOption::VALUE_NONE)
            ->addOption('progress', null, InputOption::VALUE_NONE)
            ->setHelp(<<<EOH
Usage: phpcpd [switches] <directory|file> ...

  --log-pmd <file>         Write report in PMD-CPD XML format to file.

  --min-lines <N>          Minimum number of identical lines (default: 5).
  --min-tokens <N>         Minimum number of identical tokens (default: 70).

  --exclude <dir>          Exclude <dir> from code analysis.
  --names <names>          A comma-separated list of file names to check.
                           (default: *.php)

  --help                   Prints this usage information.
  --version                Prints the version and exits.

  --progress               Show progress bar.
  --quiet                  Only print the final summary.
  --verbose                Print duplicated code.
EOH
            )
          ;
        }

        /**
         * {@inheritdoc}
         */
        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $excludes = $input->getOption('exclude');
            $logPmd = $input->getOption('log-pmd');
            $minLines = $input->getOption('min-lines');
            $minTokens = $input->getOption('min-tokens');
            $names = $input->getOption('names');
            $quiet = $input->getOption('quiet');
            $verbose = $input->getOption('verbose');

            $finder = new FinderFacade(array($input->getArgument('path')), $excludes, $names);
            $files  = $finder->findFiles();
            if (empty($files)) {
                $this->showError("No files found to scan.\n");
            }

            $strategy = new DefaultStrategy();
            $detector = new Detector($strategy, $output);

            $clones = $detector->copyPasteDetection(
              $files, $minLines, $minTokens
            );

            $printer = new ResultPrinter($output);
            $printer->printResult($clones, !$quiet, $verbose);
            unset($printer);

            if ($logPmd) {
                $pmd = new PMD($logPmd);
                $pmd->processClones($clones);
                unset($pmd);
            }

            if (count($clones) > 0) {
                exit(1);
            }
        }
    }
}
