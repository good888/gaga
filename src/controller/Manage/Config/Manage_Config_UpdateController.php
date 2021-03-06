<?php
/**
 * Created by PhpStorm.
 * User: anguoyue
 * Date: 15/08/2018
 * Time: 10:58 AM
 */

class Manage_Config_UpdateController extends Manage_CommonController
{
    /**
     * 站点管理
     */
    public function doRequest()
    {
        $response = [];
        try {
            $config['lang'] = $this->language;

            $configKey = $_POST['key'];
            $configValue = trim($_POST['value']);

            $this->ctx->Wpf_Logger->error("manage.config.update", "ke=" . $configKey . " value=" . $configValue);

            if (!in_array($configKey, SiteConfig::$configKeys)) {
                throw new Exception("config key permission error");
            }

            if (SiteConfig::SITE_PLUGIN_PLBLIC_KEY == $configKey) {
                $configValue = $this->ctx->ZalyHelper->generateStrKey(32);
            } elseif (SiteConfig::SITE_ZALY_PORT == $configKey) {
                if (empty($configValue)) {
                    $configValue = 0;
                }
            }

            //判断是否为数字
            if (in_array($configKey, SiteConfig::$numericKeys)) {
                if (!is_numeric($configValue)) {
                    throw new Exception("value is not number");
                }
            }

            if ($configKey == SiteConfig::SITE_LOGO) {
                $fileId = $configValue;
                $imageDir = WPF_LIB_DIR . "../public/site/image/";
                $this->ctx->File_Manager->moveImage($fileId, $imageDir);
            }

            if ($configKey == SiteConfig::SITE_WS_ADDRESS) {

                $this->checkWsAddress($configValue);

                //清理 sitePort && siteHost
                $this->deleteSiteConfig([SiteConfig::SITE_WS_HOST, SiteConfig::SITE_WS_PORT]);
            }

            $result = $this->updateSiteConfig($configKey, $configValue);
            if ($result) {
                $response["errCode"] = "success";
            } else {
                $response["errCode"] = "error";
                $response["errInfo"] = 'update configValue error';
            }
        } catch (Exception $e) {
            $this->ctx->Wpf_Logger->error("manage.config.update", $e);
            $response["errCode"] = "error";
            $response["errInfo"] = $e->getMessage();
        }

        echo json_encode($response);
        return;
    }


    private function updateSiteConfig($configKey, $configValue)
    {
        $tag = __CLASS__ . "->" . __FUNCTION__;

        try {
            $result = $this->ctx->SiteConfigTable->updateSiteConfig($configKey, $configValue);
            $this->ctx->Wpf_Logger->error("manage.config.update", "key=" . $configKey
                . " configValue=" . $configValue . " result=" . $result);

            if (!$result) {
                return $this->saveSiteConfig($configKey, $configValue);
            }

            return true;
        } catch (Exception $e) {
            $this->ctx->Wpf_Logger->error($tag, $e);
            return $this->saveSiteConfig($configKey, $configValue);
        }


        return false;
    }

    private function saveSiteConfig($configKey, $configValue)
    {
        $tag = __CLASS__ . "->" . __FUNCTION__;
        try {
            $result = $this->ctx->SiteConfigTable->insertSiteConfig($configKey, $configValue);

            $this->ctx->Wpf_Logger->error("manage.config.save", "key=" . $configKey
                . " configValue=" . $configValue . " result=" . $result);

            return $result;
        } catch (Exception $e) {
            $this->ctx->Wpf_Logger->error($tag, $e);
        }
        return false;
    }

    private function deleteSiteConfig(array $configKeys)
    {
        $tag = __CLASS__ . "->" . __FUNCTION__;
        try {
            if (!empty($configKeys)) {

                foreach ($configKeys as $configKey) {
                    $this->ctx->SiteConfigTable->deleteSiteConfig($configKey);
                }
            }
        } catch (Exception $e) {
            $this->logger->error($tag, $e);
        }
    }

    private function checkWsAddress($wsAddressUrl)
    {
        if (empty($wsAddressUrl)) {
            return;
        }

        $wsAddress = parse_url($wsAddressUrl);

        $schema = $wsAddress["scheme"];

        if (empty($schema)) {
            throw new Exception($this->language == 1 ? "wsAddress格式错误" : "ws address formatting error");
        } else {
            if ($schema != "ws" && $schema != "WS" && $schema != "wss" && $schema != "WSS") {
                throw new Exception($this->language == 1 ? "wsAddress格式错误" : "ws address formatting error");
            }
        }

        $host = $wsAddress["host"];
        $port = $wsAddress["port"];
        if (empty($host) || empty($port)) {
            throw new Exception($this->language == 1 ? "wsAddress格式错误" : "ws address formatting error");
        }

        return;
    }
}