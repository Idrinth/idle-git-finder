<?php

namespace De\Idrinth\FindIdleGit\Test;

use De\Idrinth\FindIdleGit\IdleGitFinder;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class IdleGitFinderTest extends TestCase
{
    /**
     * @return array
     */
    public function provideRun()
    {
        return array(
            "empty" => array(
                array(
                    "root" => array()
                ),
                ""
            ),
            "simple" => array(
                array(
                    "root" => array(
                        ".git" => array(),
                        "example.php"
                    )
                ),
                "\nX vfs://root/root (1)"
            ),
            "simple+empty" => array(
                array(
                    "root" => array(
                        ".git" => array()
                    )
                ),
                "\n✓ vfs://root/root (0)"
            ),
            "subdir" => array(
                array(
                    "root" => array(
                        "root" => array(
                            ".git" => array(),
                            "example.php",
                            "example2.php"
                        ),
                        "example.php"
                    )
                ),
                "\nX vfs://root/root/root (2)"
            ),
            "subdir+ignore" => array(
                array(
                    "root" => array(
                        "root" => array(
                            ".git" => array(),
                            "example.php",
                            "example2.php"
                        ),
                        "root2" => array(
                            ".git" => array(),
                            "example.php",
                            "example2.php"
                        ),
                        "example.php"
                    )
                ),
                "\nX vfs://root/root/root (2)",
                array("root2")
            ),
            "git in git" => array(
                array(
                    "root" => array(
                        ".git" => array(),
                        "root" => array(
                            ".git" => array(),
                            "example.php",
                            "example2.php"
                        ),
                        "root2" => array(
                            ".git" => array(),
                            "example.php",
                            "example2.php"
                        ),
                        "example.php"
                    )
                ),
                "\nX vfs://root/root (3)",
                array("root2")
            ),
            "multiple git" => array(
                array(
                    "root" => array(
                        "root" => array(
                            ".git" => array(),
                            "example.php",
                            "example2.php"
                        ),
                        "example.php"
                    ),
                    "root2" => array(
                        ".git" => array(),
                        "example.php",
                        "example2.php"
                    ),
                ),
                "\nX vfs://root/root/root (2)\nX vfs://root/root2 (2)",
            ),
            "git in svn" => array(
                array(
                    "root" => array(
                        ".svn" => array(),
                        "root" => array(
                            ".git" => array(),
                            "example.php",
                            "example2.php"
                        ),
                        "example.php"
                    ),
                    "root2" => array(
                        ".git" => array(),
                        "example.php",
                        "example2.php"
                    ),
                ),
                "\nX vfs://root/root2 (2)",
            ),
        );
    }

    /**
     * @return IdleGitFinder
     */
    private function getGitCommandFreeInstance($ignores)
    {
        $finder = $this->getMockBuilder('De\\Idrinth\\FindIdleGit\\IdleGitFinder')
            ->setConstructorArgs(array($ignores))
            ->setMethods(array('getChangedFiles'))
            ->getMockForAbstractClass();
        $finder->expects(self::any())
            ->method('getChangedFiles')
            ->willReturnCallback(function ($dir) {
                return array_diff(scandir($dir) ?: array(), array('.', '..', '.git'));
            });
        return $finder;
    }

    /**
     * @test
     * @dataProvider provideRun
     * @param array $structure
     * @param string $output
     * @param array $ignores
     * @return void
     */
    public function testRun($structure, $output, $ignores = array())
    {
        ob_start();
        $stream = vfsStream::setup("/root", null, $structure);
        $this->getGitCommandFreeInstance($ignores)->run(vfsStream::url('root'));
        $this->assertEquals($output, ob_get_clean());
        unset($stream);
    }

    /**
     * @test
     * @return void
     */
    public function testRunSelf()
    {
        ob_start();
        $instance = new IdleGitFinder(array('vendor'));
        $instance->run(dirname(__DIR__));
        $this->assertEquals("\n✓ / (0)", ob_get_clean());
    }

    /**
     * @return array
     */
    public function provideTestCreate()
    {
        $sep = "\\".DIRECTORY_SEPARATOR;
        return array(
            "no args" => array(array(), array()),
            "1 ignore" => array(array("ignore"), array("/{$sep}ignore/")),
            "1 ignore + command" => array(array("any/path/find-idle-git", "ignore"), array("/{$sep}ignore/")),
            "1 ignore, php + command" => array(
                array("/a/php/somewhere/phpexecutable", "any/path/find-idle-git", "ignore"),
                array("/{$sep}ignore/")
            ),
            "2 ignores" => array(array("ignore2t", "ignore"), array("/{$sep}ignore2t/", "/{$sep}ignore/")),
            "ignore structured" => array(array("ignore2t/ignore"), array("/{$sep}ignore2t{$sep}ignore/"))
        );
    }

    /**
     * @test
     * @dataProvider provideTestCreate
     * @return void
     */
    public function testCreate(array $args, array $ignores)
    {
        $instance = IdleGitFinder::create($args);
        $this->assertInstanceOf('De\\Idrinth\\FindIdleGit\\IdleGitFinder', $instance);
        $this->assertAttributeEquals($ignores, 'pathsToIgnore', $instance);
    }
}
