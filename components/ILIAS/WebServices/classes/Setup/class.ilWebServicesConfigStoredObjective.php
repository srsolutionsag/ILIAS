<?php

declare(strict_types=1);

/* Copyright (c) 2020 Daniel Weise <daniel.weise@concepts-and-training.de> Extended GPL, see docs/LICENSE */

use ILIAS\Setup;

/**
 * Store information about https is enabled
 */
class ilWebServicesConfigStoredObjective implements Setup\Objective
{
    protected ilWebServicesSetupConfig $config;

    public function __construct(ilWebServicesSetupConfig $config)
    {
        $this->config = $config;
    }

    public function getHash(): string
    {
        return hash("sha256", self::class);
    }

    public function getLabel(): string
    {
        return "Store information about web services in the settings";
    }

    public function isNotable(): bool
    {
        return true;
    }

    public function getPreconditions(Setup\Environment $environment): array
    {
        return [
            new \ilIniFilesPopulatedObjective(),
            new \ilSettingsFactoryExistsObjective()
        ];
    }

    public function achieve(Setup\Environment $environment): Setup\Environment
    {
        $factory = $environment->getResource(Setup\Environment::RESOURCE_SETTINGS_FACTORY);
        $settings = $factory->settingsFor("common");
        $settings->set(
            "soap_user_administration",
            $this->bool2string($this->config->isSOAPUserAdministration())
        );
        $settings->set("soap_wsdl_path", $this->config->getSOAPWsdlPath());
        $settings->set("soap_connect_timeout", (string) $this->config->getSOAPConnectTimeout());
        $settings->set("soap_response_timeout", (string) $this->config->getSoapResponseTimeout());
        $settings->set("rpc_server_host", $this->config->getRPCServerHost());
        $settings->set("rpc_server_port", (string) $this->config->getRPCServerPort());

        $settings->set('soap_internal_wsdl_path', (string) $this->config->getSoapInternalWsdlPath());
        $settings->set('soap_internal_wsdl_verify_peer', (string) $this->config->getSoapInternalWsdlVerifyPeer());
        $settings->set('soap_internal_wsdl_verify_peer_name', (string) $this->config->getSoapInternalWsdlVerifyPeerName());
        $settings->set('soap_internal_wsdl_allow_self_signed', (string) $this->config->getSoapInternalWsdlAllowSelfSigned());

        return $environment;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(Setup\Environment $environment): bool
    {
        return true;
    }

    protected function bool2string(bool $value): string
    {
        if ($value) {
            return "1";
        }
        return "0";
    }
}
