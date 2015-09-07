<?php

namespace NewupPlayground\Disco;

use NewUp\Templates\BasePackageTemplate;
use Symfony\Component\Process\Process;

/**
 * Class Package
 *
 * This playground was inspired by Kayla Daniels's "disco".
 * Check it out here https://github.com/kayladnls/disco and
 * learn something :)
 *
 * @package NewupPlayground\Disco
 */
class Package extends BasePackageTemplate
{

    /**
     * The Wikipedia instance.
     *
     * @var Wikipedia
     */
    protected $wikipedia;

    protected $isQuiet = true;

    public function __construct(Wikipedia $wiki)
    {
        $this->wikipedia = $wiki;
    }

    /**
     * Called when the builder has loaded the package class.
     *
     * @return mixed
     */
    public function builderLoaded()
    {
        // Let's kick everything off here.
        $this->findMeAPage();
    }

    /**
     * Finds the user a page that they have never seen before!
     */
    private function findMeAPage()
    {
        $article = $this->wikipedia->uniquelyRandom();

        if ($article !== false) {
            $this->line("Look what I found! <info>{$article[1]}</info>!! Sounds Fascinating");
            if ($this->confirm('Should we keep looking? [y/N]', false)) {
                $this->findMeAPage();
                return;
            } else {
                $process = $this->getProcess($article[0]);
                $process->run();

                if ($process->isSuccessful()) {
                    return;
                }
            }
        }

        $this->error('Something horrible went wrong! Please try again!');
    }

    /**
     * Gets a Process for opening a URI.
     *
     * This method attempts to detect operating systems so it
     * is inevitable that it will fail at some point.
     *
     * @param $uri
     * @return Process
     */
    private function getProcess($uri)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // ex: start "" "https://www.wikipedia.org/
            $command = 'start "" "'.$uri.'"';
        } else {
            // ex: open https://www.wikipedia.org/
            $command = 'open '.$uri;
        }

        $process = new Process('', __DIR__);
        $process->setCommandLine($command);

        return $process;
    }


}