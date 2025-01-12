require File.expand_path('../boot', __FILE__)

# Pick the frameworks you want:
require "active_model/railtie"
require "active_job/railtie"
require "active_record/railtie"
require "action_controller/railtie"
require "action_mailer/railtie"
require "action_view/railtie"
require "sprockets/railtie"
# require "rails/test_unit/railtie"

# Require the gems listed in Gemfile, including any gems
# you've limited to :test, :development, or :production.
Bundler.require(*Rails.groups)

module OnlineEditorsExampleRuby
  class Application < Rails::Application

    config.middleware.insert_before 0, Rack::Cors do
      allow do
        origins '*'
        resource '*',
                 headers: :any,
                 methods: [:get, :post, :patch, :delete, :put, :options]
      end
    end

    Rails.configuration.version="1.6.0"

    Rails.configuration.fileSizeMax=5242880
    Rails.configuration.storagePath="app_data"
    Rails.configuration.timeout=120

    Rails.configuration.fillDocs=".docx|.oform"
    Rails.configuration.viewedDocs=".djvu|.oxps|.pdf|.xps"
    Rails.configuration.editedDocs=".csv|.docm|.docx|.docxf|.dotm|.dotx|.epub|.fb2|.html|.odp|.ods|.odt|.otp|.ots|.ott|.potm|.potx|.ppsm|.ppsx|.pptm|.pptx|.rtf|.txt|.xlsm|.xlsx|.xltm|.xltx"
    Rails.configuration.convertDocs=".doc|.dot|.dps|.dpt|.epub|.et|.ett|.fb2|.fodp|.fods|.fodt|.htm|.html|.mht|.mhtml|.odp|.ods|.odt|.otp|.ots|.ott|.pot|.pps|.ppt|.rtf|.stw|.sxc|.sxi|.sxw|.wps|.wpt|.xls|.xlsb|.xlt|.xml"

    Rails.configuration.urlSite="http://documentserver/"
    Rails.configuration.urlConverter="ConvertService.ashx"
    Rails.configuration.urlApi="web-apps/apps/api/documents/api.js"
    Rails.configuration.urlPreloader="web-apps/apps/api/documents/cache-scripts.html"
    Rails.configuration.commandUrl="coauthoring/CommandService.ashx"

    Rails.configuration.urlExample=""

    Rails.configuration.jwtSecret = ""
    Rails.configuration.header="Authorization"
    Rails.configuration.token_use_for_request=true

    Rails.configuration.verify_peer_off = "true"

    Rails.configuration.languages={
      'en' => 'English',
      'hy' => 'Armenian',
      'az' => 'Azerbaijani',
      'eu' => 'Basque',
      'be' => 'Belarusian',
      'bg' => 'Bulgarian',
      'ca' => 'Catalan',
      'zh' => 'Chinese (Simplified)',
      'zh-TW' => 'Chinese (Traditional)',
      'cs' => 'Czech',
      'da' => 'Danish',
      'nl' => 'Dutch',
      'fi' => 'Finnish',
      'fr' => 'French',
      'gl' => 'Galego',
      'de' => 'German',
      'el' => 'Greek',
      'hu' => 'Hungarian',
      'id' => 'Indonesian',
      'it' => 'Italian',
      'ja' => 'Japanese',
      'ko' => 'Korean',
      'lo' => 'Lao',
      'lv' => 'Latvian',
      'ms' => 'Malay (Malaysia)',
      'no' => 'Norwegian',
      'pl' => 'Polish',
      'pt' => 'Portuguese (Brazil)',
      'pt-PT' => 'Portuguese (Portugal)',
      'ro' => 'Romanian',
      'ru' => 'Russian',
      'si' => 'Sinhala (Sri Lanka)',
      'sk' => 'Slovak',
      'sl' => 'Slovenian',
      'es' => 'Spanish',
      'sv' => 'Swedish',
      'tr' => 'Turkish',
      'uk' => 'Ukrainian',
      'vi' => 'Vietnamese',
      'aa-AA' => 'Test Language'
    }
  end
end
