<?php
namespace Netresearch\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Base implements \Iterator
{
    protected $data;
    protected $ask;
    protected $confirm;

    protected $confirmedData = array();

    protected $output;
    protected $command;

    protected $currentOffset = 0;

    /**
     * create config instance
     *
     * @param mixed $config Either config file path or configuration array
     * @return void
     */
    public function __construct($config, $ask=null, $confirm=null)
    {
        if (is_array($config)) {
            $this->data = $config;
        } else {
            $filepath = $config;
            $filetype = strtolower(strrchr($filepath, '.'));
            if (!file_exists($filepath)) {
                throw new \Exception("Configuration file $filepath not found");
            }
            switch($filetype) {
                case '.php':
                    $this->data = include $filepath;
                    break;

                case '.ini';
                    $data = new \Zend_Config_Ini(
                        $filepath,
                        null,
                        array('allowModifications' => true)
                    );
                    $this->data = $data->toArray();
                    break;

                case '.json':
                    $this->data = json_decode(file_get_contents($filepath), true);
                    break;

                case '.yml':
                case '.yaml':
                    $this->data = Yaml::parse($filepath);
                    break;
            }
        }
        $this->ask = array_key_exists('ask', $this->data) ? $this->data['ask'] : array();
        if (false == is_array($this->ask)) {
            $this->ask = is_null($ask) ? array() : array($ask);
        }
        if ($ask) {
            $this->ask = array_merge($this->ask, $ask);
        }

        $this->confirm = array_key_exists('confirm', $this->data) ? $this->data['confirm'] : array();
        if (false == is_array($this->confirm)) {
            $this->confirm = is_null($confirm) ? array() : array($confirm);
        }
        if ($confirm) {
            $this->confirm = array_merge($this->confirm, $confirm);
        }
    }

    /**
     * access configuration data
     *
     * @param string $key Configuration property
     * @return mixed
     */
    public function __get($key)
    {
        $value = null;
        $ask     = $this->getPaths('ask', $key);
        $confirm = $this->getPaths('confirm', $key);
        if (isset($this->data[$key])) {
            $value = $this->data[$key];
            $this->handle('confirm', $key, $value);
            if (is_array($value)) {
                $value = new Base($value, $ask, $confirm);
                $value->setCommand($this->command);
                $value->setOutput($this->output);
            }
            return $value;
        }
        return $this->handle('ask', $key, $value);
    }

    /**
     * get paths to be confirmed or asked
     *
     * @param string $mode "ask"|"confirm"
     * @param string $key  Configuration property
     * @return array
     */
    protected function getPaths($mode, $key, $debug=false)
    {
        $requests = array();
        foreach ($this->$mode as $configPath) {
            $configPath = (false === strpos($configPath, '|'))
                ? $configPath
                : substr(strrchr($configPath, '|'), 1);
            $steps = explode('.', $configPath);
            if ($steps[0] == $key) {
                if (1 < count($steps)) {
                    $requests[] = str_replace('|', '.', substr($configPath, 0, strlen($key)))
                        . '|' . substr($configPath, strlen($key)+1);
                }
            }
        }
        return $requests;
    }

    /**
     * set configuration data
     *
     * @param string $key  Configuration property
     * @param mixed $value Configuration value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    protected function handle($mode, $key, $value)
    {
        foreach ($this->$mode as $configPath) {
            $configPath = (false === strpos($configPath, '|'))
                ? $configPath
                : substr(strrchr($configPath, '|'), 1);
            $readablePath = ucwords(str_replace(array('.', '|'), ' ', $configPath));
            if ('ask' == $mode && is_null($value) && $key == $configPath) {
                $dialog = $this->command->getHelperSet()->get('dialog');
                $value = $dialog->ask(
                    $this->output,
                    sprintf('<question>%s?</question> ', $readablePath),
                    false
                );
            }
            if ('confirm' == $mode
                && $key == $configPath
            ) {
                $dialog = $this->command->getHelperSet()->get('dialog');
                $confirmation = $dialog->askConfirmation(
                    $this->output,
                    sprintf('<question>%s %s?</question> ', $readablePath, $value),
                    ('confirm' == $mode)
                );
                if (!$confirmation) {
                    throw new \Exception(sprintf(
                        'Stopped execution due to unconfirmed %s!',
                        $readablePath
                    ));
                }
            }
        }
        return $value;
    }

    /**
     * determine a configuration value (take from config, let the user confirm it, or ask user)
     *
     * @deprecated
     *
     * @param mixed $path Configuration path
     * @return mixed
     */
    public function determine($path)
    {
        if (array_key_exists($path, $this->confirmedData)) {
            return $this->confirmedData[$path];
        }
        $readablePath = ucwords(str_replace('.', ' ', $path));
        $steps = explode('.', $path);
        $value = $this;
        $step = current($steps);
        while (is_array($value) || $value instanceof Config) {
            $value = is_array($value) ? $value[$step] : $value->$step;
            $step = next($steps);
        }
        if (is_null($value) && $this->ask && in_array($path, $this->ask)) {
            $dialog = $this->command->getHelperSet()->get('dialog');
            $value = $dialog->ask(
                $this->output,
                sprintf('<question>%s?</question> ', $readablePath),
                false
            );
            $subConfig = $this;
            foreach ($steps as $step) {
                $subConfig = $subConfig->$step;
            }
            $subConfig = $value;
        }
        if ($this->confirm && in_array($path, $this->confirm)) {
            $dialog = $this->command->getHelperSet()->get('dialog');
            die(var_dump(__FILE__ . ' on line ' . __LINE__ . ':', $readablePath, $value));
            $confirmation = $dialog->askConfirmation(
                $this->output,
                sprintf('<question>%s %s (y)?</question> ', $readablePath, $value),
                true
            );
        }
        $this->confirmedData[$path] = $value;
        return $value;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function setCommand(Command $command)
    {
        $this->command = $command;
    }

    public function current()
    {
        return $this->__get(array_keys($this->data)[$this->currentOffset]);
    }

    public function key()
    {
        return array_keys($this->data)[$currentOffset];
    }

    public function next()
    {
        return ++$this->currentOffset;
    }

    public function rewind()
    {
        $this->currentOffset = 0;
    }

    public function valid()
    {
        return isset($this->data[array_keys($this->data)[$this->currentOffset]]);
    }
}
