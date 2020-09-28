<?php
declare(strict_types=1);

namespace PhpToJavascript\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Utility\Text;
use PhpToJavascript\Plugin;

/**
 * generate_php_js command.
 *
 * Generates the plugin's main javascript file.
 */
class GeneratePhpJsCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->addOption('function', [
            'short' => 'f',
            'help' => 'Name of the global function through which your php variables will be available',
            'default' => Configure::read('PhpToJavascript.function', 'p'),
        ]);
        $parser->addOption('storage', [
            'short' => 's',
            'help' => 'Name of the global javascript variable where your php variables will be stored',
            'default' => Configure::read('PhpToJavascript.storage', '__php2javascript'),
        ]);

        return $parser;
    }

    /**
     * Generates the plugin's main javascript file.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $path = $io->ask('Where should the plugin\'s javascript file be saved ?', WWW_ROOT . 'js');

        if (!is_dir($path)) {
            $io->error('The target directory does not exist');

            return self::CODE_ERROR;
        }

        $filename = $io->ask('What should be the filename (without ext.) of the plugin\'s javascript file ?', 'php2js');
        $template = (new Plugin())->getConfigPath() . 'main.js-template';

        if (is_file($template) && is_readable($template)) {
            $io->info('Template file found and readable');
        } else {
            $io->error('Template file not found or not readable');

            return self::CODE_ERROR;
        }

        $data = Text::insert(file_get_contents($template), [
            'function' => $args->getOption('function'),
            'storage' => $args->getOption('storage'),
        ]);

        if (file_put_contents($path . DS . $filename . '.js', $data) === false) {
            $io->error('The file creation failed');

            return self::CODE_ERROR;
        } else {
            $io->success('File successfully created');
        }

        return self::CODE_SUCCESS;
    }
}
