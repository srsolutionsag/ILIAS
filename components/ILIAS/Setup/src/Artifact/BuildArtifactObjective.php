<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Setup\Artifact;

use ILIAS\Setup;


/**
 * This is an objective to build some artifact.
 */
abstract class BuildArtifactObjective implements Setup\Objective
{
    private const ARTIFACTS = __DIR__ . "/../../../../../artifacts";

    /**
     * @return string The path where the artifact should be stored. You can use this path to require the artifact.
     */
    final public static function PATH(): string
    {
        return realpath(self::ARTIFACTS) . "/" . md5(static::class) . ".php";
    }

    private const COMPONENTS_DIRECTORY = "components";

    /**
     * Get the filename where the builder wants to put its artifact.
     *
     * This is understood to be a path relative to the ILIAS root directory.
     */
    abstract public function getArtifactName(): string;

    /**
     * Build the artifact based. If you want to use the environment
     * reimplement `buildIn` instead.
     */
    abstract public function build(): Setup\Artifact;

    /**
     * Builds an artifact in some given Environment.
     *
     * Defaults to just dropping the environment and using `build`.
     *
     * If you want to reimplement this, you most probably also want to reimplement
     * `getPreconditions` to prepare the environment properly.
     */
    public function buildIn(Setup\Environment $env): Setup\Artifact
    {
        return $this->build();
    }

    /**
     * Defaults to no preconditions.
     *
     * @inheritdocs
     */
    public function getPreconditions(Setup\Environment $environment): array
    {
        return [];
    }

    /**
     * Uses hashed Path.
     *
     * @inheritdocs
     */
    public function getHash(): string
    {
        return hash("sha256", $this->getArtifactName());
    }

    /**
     * Defaults to 'Build ' . $this->getArtifactName().' Artifact'.
     *
     * @inheritdocs
     */
    public function getLabel(): string
    {
        return 'Build ' . $this->getArtifactName().' Artifact';
    }

    /**
     * Defaults to 'true'.
     *
     * @inheritdocs
     */
    public function isNotable(): bool
    {
        return true;
    }

    /**
     * Builds the artifact and puts it in its location.
     *
     * @inheritdocs
     */
    public function achieve(Setup\Environment $environment): Setup\Environment
    {
        $artifact = $this->buildIn($environment);

        $path = $this->getAbsoluteArtifactPath();

        $this->makeDirectoryFor($path);

        file_put_contents($path, $artifact->serialize());

        return $environment;
    }

    private function getRelativeArtifactPath(): string
    {
        $here = realpath(__DIR__ . "/../../../../../");
        return "./" . ltrim(str_replace($here, "", $this->getAbsoluteArtifactPath()), "/");
    }

    private function getAbsoluteArtifactPath(): string
    {
        $here = realpath(__DIR__ . "/../../../../../");

        $artifact_path = static::PATH();

        switch (true) {
            case strpos($artifact_path, "/") === 0:
            case strpos($artifact_path, "./") === 0:
                return $this->realpath($artifact_path);
                break;
            case strpos($artifact_path, "../" . self::COMPONENTS_DIRECTORY . "") === 0:
                return $this->realpath($here . "/" . self::COMPONENTS_DIRECTORY . "/" . $artifact_path);
                break;

            case strpos($artifact_path, "../") === 0:
                $dirname = dirname((new \ReflectionClass($this))->getFileName());
                return $this->realpath($dirname . "/" . $artifact_path);
                break;
            default:
                return $this->realpath($artifact_path);
        }
    }

    public function isApplicable(Setup\Environment $environment): bool
    {
        return true;
    }

    protected function makeDirectoryFor(string $path): void
    {
        $dir = pathinfo($path)["dirname"];
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * @description we cannot use php's realpath because it does not work with with non existing paths.
     *              Thanks to Beat Christen, https://stackoverflow.com/questions/20522605/what-is-the-best-way-to-resolve-a-relative-path-like-realpath-for-non-existing
     */
    protected function realpath(string $filename): string
    {
        $path = [];
        foreach (explode('/', $filename) as $part) {
            // ignore parts that have no value
            if (empty($part) || $part === '.') {
                continue;
            }

            if ($part !== '..') {
                // cool, we found a new part
                $path[] = $part;
            } elseif (count($path) > 0) {
                // going back up? sure
                array_pop($path);
            } else {
                // now, here we don't like
                throw new \RuntimeException('Climbing above the root is not permitted.');
            }
        }

        return "/" . implode('/', $path);
    }
}
