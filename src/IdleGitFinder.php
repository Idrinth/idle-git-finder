<?php

namespace De\Idrinth\FindIdleGit;

use FilesystemIterator;

class IdleGitFinder
{
    /**
     * @var string
     */
    private $replacementRegex;

    /**
     * @var string[]
     */
    private $pathsToIgnore = array();

    /**
     * @var string[]
     */
    private static $fileDiffignore = array('.', '..', '.git', '.svn');

    /**
     * @param string[] $pathsToIgnore
     */
    public function __construct(array $pathsToIgnore = array())
    {
        $this->replacementRegex = '/^' . preg_quote(getcwd(), '/') . '/';
        $separator = DIRECTORY_SEPARATOR;
        foreach ($pathsToIgnore as $path) {
            $this->pathsToIgnore[] = '/' . preg_quote($separator . str_replace('/', $separator, $path), '/') . '/';
        }
    }

    /**
     * @param string $path
     * @return string
     */
    private function simplifyPath($path)
    {
        return str_replace('\\', '/', preg_replace($this->replacementRegex, '', $path)) ?: '/';
    }

    /**
     * @param string $success
     * @param string $path
     * @param int $files
     */
    private function display($success, $path, $files)
    {
        echo "\n$success {$this->simplifyPath($path)} ($files)";
    }

    /**
     * @param string $dir
     * @return array
     */
    protected function getChangedFiles($dir)
    {
        $files = array();
        exec("cd " . escapeshellarg($dir) . " && git status --porcelain", $files);
        return $files;
    }

    /**
     * @param string $dir
     * @return void
     */
    public function run($dir)
    {
        foreach ($this->pathsToIgnore as $regex) {
            if (preg_match($regex, $dir)) {
                return;
            }
        }
        foreach ($this->getSubdirs($dir) as $subdir) {
            $this->run($subdir);
        }
    }

    /**
     * @param string $directory
     * @return FilesystemIterator|string[]
     */
    private function getSubDirIterator($directory)
    {
        return new FilesystemIterator(
            $directory,
            FilesystemIterator::SKIP_DOTS|FilesystemIterator::CURRENT_AS_PATHNAME
        );
    }

    /**
     * @param string $directory
     * @return string[]
     */
    private function getSubdirs($directory)
    {
        $dirs = array();
        foreach ($this->getSubDirIterator($directory) as $file) {
            if (is_dir($file)) {
                if (preg_match('/\.svn$/', $file)) {
                    return array();
                }
                if (preg_match('/\.git$/', $file)) {
                    $modified = count($this->getChangedFiles($directory));
                    $this->display(
                        $this->isDirectoryUnchanged($modified, $directory) ? "✓" : "X",
                        $directory,
                        $modified
                    );
                    return array();
                }
                $dirs[] = $file;
            }
        }
        return $dirs;
    }

    /**
     * @param int $modified
     * @param string $directory
     * @return boolean
     */
    private function isDirectoryUnchanged($modified, $directory)
    {
        if ($modified === 0) {
            return true;
        }
        return count(array_diff(scandir($directory) ?: array(), self::$fileDiffignore)) === 0;
    }

    /**
     * @param string[] $pathsToIgnore
     * @return IdleGitFinder
     */
    public static function create(array $pathsToIgnore = array())
    {
        if (isset($pathsToIgnore[1]) && preg_match('/(^|\/|\\\\)find-idle-git$/', $pathsToIgnore[1])) {
            array_shift($pathsToIgnore);
        }
        if (isset($pathsToIgnore[0]) && preg_match('/(^|\/|\\\\)find-idle-git$/', $pathsToIgnore[0])) {
            array_shift($pathsToIgnore);
        }
        return new self(array_values($pathsToIgnore));
    }
}
