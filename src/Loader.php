<?php

namespace Alterload;

use Composer\Autoload\ClassLoader;
use RuntimeException;

class Loader
{
    protected $projectPath = null;

    protected $baseLoader = null;

    protected $configFileName = '.alterload.ini';

    public static function alter(ClassLoader $baseLoader)
    {
        return (new self($baseLoader))->load();
    }

    public static function getLinks()
    {
        $self = new self();
        if (!$config = $self->getConfig()) {
            return false;
        }
        $map = array_merge($self->getBaseLoader()->getPrefixesPsr4(), $self->getBaseLoader()->getPrefixes());
        $res = ['projectPath' => $self->getProjectPath(), 'links' => []];
        foreach ($config as $ns => $path) {
            if (0 === stripos($ns, 'link:')) {
                $ns = explode(':', $ns);
                $clas = trim($ns[count($ns)-1]);
                if (isset($map[$clas])) {
                    $res['links'] []= [
                        'class' => $clas,
                        'path' => $map[$clas][0],
                        'target' => $self->slashTail($path),
                    ];
                }
            }
        }

        return $res;
    }

    public function __construct(ClassLoader $baseLoader = null)
    {
        $this->setProjectPath(__DIR__.'/../../../../');
        if (null === $baseLoader) {
            $baseLoader = require $this->projectPath.'vendor/autoload.php';
        }
        $this->setBaseLoader($baseLoader);
    }

    public function load()
    {
        if (!$config = $this->getConfig()) {
            return false;
        }
        foreach ($config as $ns => $path) {
            $this->loadOne($ns, $path);
        }

        return true;
    }

    protected function loadOne($ns, $path)
    {
        $path = $this->slashTail($path);
        if (false === strpos($ns, ':')) {
            $ns = 'psr-4:'.$ns;
        }
        list($standard, $ns) = explode(':', $ns);
        switch (strtolower($standard)) {
            case 'psr-4':
            case 'psr4':
                $this->baseLoader->addPsr4($ns, $path, true);
                break;
            case 'psr-0':
            case 'psr0':
                $this->baseLoader->add($ns, $path, true);
                break;
            case 'link':
                // the link option. do not load
                break;
            default:
                throw new RuntimeException('Unsupported standard: '.$standard);
                break;
        }
    }

    protected function getConfig()
    {
        if ($filename = $this->getConfigFile()) {
            return parse_ini_file($filename);
        }

        return false;
    }

    protected function getConfigFile()
    {
        // Look for config in your project basepath
        if ($this->projectPath) {
            if (file_exists($this->projectPath.$this->configFileName)) {
                return $this->projectPath.$this->configFileName;
            }
        }

        // Look for config in your home directory
        if (getenv('HOME') && file_exists(getenv('HOME').$this->configFileName)) {
            return getenv('HOME').$this->configFileName;
        }

        // Look for config in your system config directory
        if (file_exists('/etc/'.$this->configFileName)) {
            return '/etc/'.$this->configFileName;
        }

        // Nothing found
        return null;
    }

    public function setProjectPath($projectPath)
    {
        $this->projectPath = $this->slashTail($projectPath);

        if (!file_exists($this->projectPath)) {
            throw new RuntimeException('Project path does not exist: '.$projectPath);
        }
        if (!file_exists($this->projectPath.'/composer.json')) {
            throw new RuntimeException('Invalid project path: '.$this->projectPath.' (expecting a composer.json file there)');
        }

        return $this;
    }

    public function getProjectPath()
    {
        return $this->projectPath;
    }

    protected function setBaseLoader(ClassLoader $baseLoader)
    {
        $this->baseLoader = $baseLoader;

        return $this;
    }

    public function getBaseLoader()
    {
        return $this->baseLoader;
    }

    private function slashTail($v)
    {
        return rtrim($v, '/').'/';
    }
}
