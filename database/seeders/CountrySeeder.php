<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['code' => 'AF', 'name' => 'Afghanistan', 'currency_code' => 'AFN', 'currency_symbol' => '؋', 'currency_name' => 'Afghan Afghani'],
            ['code' => 'AL', 'name' => 'Albania', 'currency_code' => 'ALL', 'currency_symbol' => 'L', 'currency_name' => 'Albanian Lek'],
            ['code' => 'DZ', 'name' => 'Algeria', 'currency_code' => 'DZD', 'currency_symbol' => 'د.ج', 'currency_name' => 'Algerian Dinar'],
            ['code' => 'AO', 'name' => 'Angola', 'currency_code' => 'AOA', 'currency_symbol' => 'Kz', 'currency_name' => 'Angolan Kwanza'],
            ['code' => 'AR', 'name' => 'Argentina', 'currency_code' => 'ARS', 'currency_symbol' => '$', 'currency_name' => 'Argentine Peso'],
            ['code' => 'AM', 'name' => 'Armenia', 'currency_code' => 'AMD', 'currency_symbol' => '֏', 'currency_name' => 'Armenian Dram'],
            ['code' => 'AU', 'name' => 'Australia', 'currency_code' => 'AUD', 'currency_symbol' => 'A$', 'currency_name' => 'Australian Dollar'],
            ['code' => 'AT', 'name' => 'Austria', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_name' => 'Euro'],
            ['code' => 'AZ', 'name' => 'Azerbaijan', 'currency_code' => 'AZN', 'currency_symbol' => '₼', 'currency_name' => 'Azerbaijani Manat'],
            ['code' => 'BH', 'name' => 'Bahrain', 'currency_code' => 'BHD', 'currency_symbol' => '.د.ب', 'currency_name' => 'Bahraini Dinar'],
            ['code' => 'BD', 'name' => 'Bangladesh', 'currency_code' => 'BDT', 'currency_symbol' => '৳', 'currency_name' => 'Bangladeshi Taka'],
            ['code' => 'BY', 'name' => 'Belarus', 'currency_code' => 'BYN', 'currency_symbol' => 'Br', 'currency_name' => 'Belarusian Ruble'],
            ['code' => 'BE', 'name' => 'Belgium', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_name' => 'Euro'],
            ['code' => 'BJ', 'name' => 'Benin', 'currency_code' => 'XOF', 'currency_symbol' => 'CFA', 'currency_name' => 'West African CFA Franc'],
            ['code' => 'BO', 'name' => 'Bolivia', 'currency_code' => 'BOB', 'currency_symbol' => 'Bs.', 'currency_name' => 'Bolivian Boliviano'],
            ['code' => 'BR', 'name' => 'Brazil', 'currency_code' => 'BRL', 'currency_symbol' => 'R$', 'currency_name' => 'Brazilian Real'],
            ['code' => 'BN', 'name' => 'Brunei', 'currency_code' => 'BND', 'currency_symbol' => 'B$', 'currency_name' => 'Brunei Dollar'],
            ['code' => 'BG', 'name' => 'Bulgaria', 'currency_code' => 'BGN', 'currency_symbol' => 'лв', 'currency_name' => 'Bulgarian Lev'],
            ['code' => 'BF', 'name' => 'Burkina Faso', 'currency_code' => 'XOF', 'currency_symbol' => 'CFA', 'currency_name' => 'West African CFA Franc'],
            ['code' => 'KH', 'name' => 'Cambodia', 'currency_code' => 'KHR', 'currency_symbol' => '៛', 'currency_name' => 'Cambodian Riel'],
            ['code' => 'CM', 'name' => 'Cameroon', 'currency_code' => 'XAF', 'currency_symbol' => 'FCFA', 'currency_name' => 'Central African CFA Franc'],
            ['code' => 'CA', 'name' => 'Canada', 'currency_code' => 'CAD', 'currency_symbol' => 'C$', 'currency_name' => 'Canadian Dollar'],
            ['code' => 'CL', 'name' => 'Chile', 'currency_code' => 'CLP', 'currency_symbol' => '$', 'currency_name' => 'Chilean Peso'],
            ['code' => 'CN', 'name' => 'China', 'currency_code' => 'CNY', 'currency_symbol' => '¥', 'currency_name' => 'Chinese Yuan'],
            ['code' => 'CO', 'name' => 'Colombia', 'currency_code' => 'COP', 'currency_symbol' => '$', 'currency_name' => 'Colombian Peso'],
            ['code' => 'CD', 'name' => 'Congo (DRC)', 'currency_code' => 'CDF', 'currency_symbol' => 'FC', 'currency_name' => 'Congolese Franc'],
            ['code' => 'CR', 'name' => 'Costa Rica', 'currency_code' => 'CRC', 'currency_symbol' => '₡', 'currency_name' => 'Costa Rican Colón'],
            ['code' => 'CI', 'name' => 'Côte d\'Ivoire', 'currency_code' => 'XOF', 'currency_symbol' => 'CFA', 'currency_name' => 'West African CFA Franc'],
            ['code' => 'HR', 'name' => 'Croatia', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_name' => 'Euro'],
            ['code' => 'CU', 'name' => 'Cuba', 'currency_code' => 'CUP', 'currency_symbol' => '$', 'currency_name' => 'Cuban Peso'],
            ['code' => 'CZ', 'name' => 'Czech Republic', 'currency_code' => 'CZK', 'currency_symbol' => 'Kč', 'currency_name' => 'Czech Koruna'],
            ['code' => 'DK', 'name' => 'Denmark', 'currency_code' => 'DKK', 'currency_symbol' => 'kr', 'currency_name' => 'Danish Krone'],
            ['code' => 'DO', 'name' => 'Dominican Republic', 'currency_code' => 'DOP', 'currency_symbol' => 'RD$', 'currency_name' => 'Dominican Peso'],
            ['code' => 'EC', 'name' => 'Ecuador', 'currency_code' => 'USD', 'currency_symbol' => '$', 'currency_name' => 'US Dollar'],
            ['code' => 'EG', 'name' => 'Egypt', 'currency_code' => 'EGP', 'currency_symbol' => 'E£', 'currency_name' => 'Egyptian Pound'],
            ['code' => 'SV', 'name' => 'El Salvador', 'currency_code' => 'USD', 'currency_symbol' => '$', 'currency_name' => 'US Dollar'],
            ['code' => 'ET', 'name' => 'Ethiopia', 'currency_code' => 'ETB', 'currency_symbol' => 'Br', 'currency_name' => 'Ethiopian Birr'],
            ['code' => 'FI', 'name' => 'Finland', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_name' => 'Euro'],
            ['code' => 'FR', 'name' => 'France', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_name' => 'Euro'],
            ['code' => 'DE', 'name' => 'Germany', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_name' => 'Euro'],
            ['code' => 'GH', 'name' => 'Ghana', 'currency_code' => 'GHS', 'currency_symbol' => 'GH₵', 'currency_name' => 'Ghanaian Cedi'],
            ['code' => 'GR', 'name' => 'Greece', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_name' => 'Euro'],
            ['code' => 'GT', 'name' => 'Guatemala', 'currency_code' => 'GTQ', 'currency_symbol' => 'Q', 'currency_name' => 'Guatemalan Quetzal'],
            ['code' => 'HN', 'name' => 'Honduras', 'currency_code' => 'HNL', 'currency_symbol' => 'L', 'currency_name' => 'Honduran Lempira'],
            ['code' => 'HK', 'name' => 'Hong Kong', 'currency_code' => 'HKD', 'currency_symbol' => 'HK$', 'currency_name' => 'Hong Kong Dollar'],
            ['code' => 'HU', 'name' => 'Hungary', 'currency_code' => 'HUF', 'currency_symbol' => 'Ft', 'currency_name' => 'Hungarian Forint'],
            ['code' => 'IN', 'name' => 'India', 'currency_code' => 'INR', 'currency_symbol' => '₹', 'currency_name' => 'Indian Rupee'],
            ['code' => 'ID', 'name' => 'Indonesia', 'currency_code' => 'IDR', 'currency_symbol' => 'Rp', 'currency_name' => 'Indonesian Rupiah'],
            ['code' => 'IR', 'name' => 'Iran', 'currency_code' => 'IRR', 'currency_symbol' => '﷼', 'currency_name' => 'Iranian Rial'],
            ['code' => 'IQ', 'name' => 'Iraq', 'currency_code' => 'IQD', 'currency_symbol' => 'ع.د', 'currency_name' => 'Iraqi Dinar'],
            ['code' => 'IE', 'name' => 'Ireland', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_name' => 'Euro'],
            ['code' => 'IL', 'name' => 'Israel', 'currency_code' => 'ILS', 'currency_symbol' => '₪', 'currency_name' => 'Israeli Shekel'],
            ['code' => 'IT', 'name' => 'Italy', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_name' => 'Euro'],
            ['code' => 'JM', 'name' => 'Jamaica', 'currency_code' => 'JMD', 'currency_symbol' => 'J$', 'currency_name' => 'Jamaican Dollar'],
            ['code' => 'JP', 'name' => 'Japan', 'currency_code' => 'JPY', 'currency_symbol' => '¥', 'currency_name' => 'Japanese Yen'],
            ['code' => 'JO', 'name' => 'Jordan', 'currency_code' => 'JOD', 'currency_symbol' => 'JD', 'currency_name' => 'Jordanian Dinar'],
            ['code' => 'KZ', 'name' => 'Kazakhstan', 'currency_code' => 'KZT', 'currency_symbol' => '₸', 'currency_name' => 'Kazakhstani Tenge'],
            ['code' => 'KE', 'name' => 'Kenya', 'currency_code' => 'KES', 'currency_symbol' => 'KSh', 'currency_name' => 'Kenyan Shilling'],
            ['code' => 'KR', 'name' => 'South Korea', 'currency_code' => 'KRW', 'currency_symbol' => '₩', 'currency_name' => 'South Korean Won'],
            ['code' => 'KW', 'name' => 'Kuwait', 'currency_code' => 'KWD', 'currency_symbol' => 'د.ك', 'currency_name' => 'Kuwaiti Dinar'],
            ['code' => 'LA', 'name' => 'Laos', 'currency_code' => 'LAK', 'currency_symbol' => '₭', 'currency_name' => 'Lao Kip'],
            ['code' => 'LB', 'name' => 'Lebanon', 'currency_code' => 'LBP', 'currency_symbol' => 'ل.ل', 'currency_name' => 'Lebanese Pound'],
            ['code' => 'LY', 'name' => 'Libya', 'currency_code' => 'LYD', 'currency_symbol' => 'ل.د', 'currency_name' => 'Libyan Dinar'],
            ['code' => 'MY', 'name' => 'Malaysia', 'currency_code' => 'MYR', 'currency_symbol' => 'RM', 'currency_name' => 'Malaysian Ringgit'],
            ['code' => 'MX', 'name' => 'Mexico', 'currency_code' => 'MXN', 'currency_symbol' => '$', 'currency_name' => 'Mexican Peso'],
            ['code' => 'MA', 'name' => 'Morocco', 'currency_code' => 'MAD', 'currency_symbol' => 'د.م.', 'currency_name' => 'Moroccan Dirham'],
            ['code' => 'MZ', 'name' => 'Mozambique', 'currency_code' => 'MZN', 'currency_symbol' => 'MT', 'currency_name' => 'Mozambican Metical'],
            ['code' => 'MM', 'name' => 'Myanmar', 'currency_code' => 'MMK', 'currency_symbol' => 'K', 'currency_name' => 'Myanmar Kyat'],
            ['code' => 'NP', 'name' => 'Nepal', 'currency_code' => 'NPR', 'currency_symbol' => 'रू', 'currency_name' => 'Nepalese Rupee'],
            ['code' => 'NL', 'name' => 'Netherlands', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_name' => 'Euro'],
            ['code' => 'NZ', 'name' => 'New Zealand', 'currency_code' => 'NZD', 'currency_symbol' => 'NZ$', 'currency_name' => 'New Zealand Dollar'],
            ['code' => 'NG', 'name' => 'Nigeria', 'currency_code' => 'NGN', 'currency_symbol' => '₦', 'currency_name' => 'Nigerian Naira'],
            ['code' => 'NO', 'name' => 'Norway', 'currency_code' => 'NOK', 'currency_symbol' => 'kr', 'currency_name' => 'Norwegian Krone'],
            ['code' => 'OM', 'name' => 'Oman', 'currency_code' => 'OMR', 'currency_symbol' => 'ر.ع.', 'currency_name' => 'Omani Rial'],
            ['code' => 'PK', 'name' => 'Pakistan', 'currency_code' => 'PKR', 'currency_symbol' => '₨', 'currency_name' => 'Pakistani Rupee'],
            ['code' => 'PA', 'name' => 'Panama', 'currency_code' => 'USD', 'currency_symbol' => '$', 'currency_name' => 'US Dollar'],
            ['code' => 'PY', 'name' => 'Paraguay', 'currency_code' => 'PYG', 'currency_symbol' => '₲', 'currency_name' => 'Paraguayan Guarani'],
            ['code' => 'PE', 'name' => 'Peru', 'currency_code' => 'PEN', 'currency_symbol' => 'S/.', 'currency_name' => 'Peruvian Sol'],
            ['code' => 'PH', 'name' => 'Philippines', 'currency_code' => 'PHP', 'currency_symbol' => '₱', 'currency_name' => 'Philippine Peso'],
            ['code' => 'PL', 'name' => 'Poland', 'currency_code' => 'PLN', 'currency_symbol' => 'zł', 'currency_name' => 'Polish Zloty'],
            ['code' => 'PT', 'name' => 'Portugal', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_name' => 'Euro'],
            ['code' => 'QA', 'name' => 'Qatar', 'currency_code' => 'QAR', 'currency_symbol' => 'ر.ق', 'currency_name' => 'Qatari Riyal'],
            ['code' => 'RO', 'name' => 'Romania', 'currency_code' => 'RON', 'currency_symbol' => 'lei', 'currency_name' => 'Romanian Leu'],
            ['code' => 'RU', 'name' => 'Russia', 'currency_code' => 'RUB', 'currency_symbol' => '₽', 'currency_name' => 'Russian Ruble'],
            ['code' => 'RW', 'name' => 'Rwanda', 'currency_code' => 'RWF', 'currency_symbol' => 'FRw', 'currency_name' => 'Rwandan Franc'],
            ['code' => 'SA', 'name' => 'Saudi Arabia', 'currency_code' => 'SAR', 'currency_symbol' => 'ر.س', 'currency_name' => 'Saudi Riyal'],
            ['code' => 'SN', 'name' => 'Senegal', 'currency_code' => 'XOF', 'currency_symbol' => 'CFA', 'currency_name' => 'West African CFA Franc'],
            ['code' => 'RS', 'name' => 'Serbia', 'currency_code' => 'RSD', 'currency_symbol' => 'din.', 'currency_name' => 'Serbian Dinar'],
            ['code' => 'SG', 'name' => 'Singapore', 'currency_code' => 'SGD', 'currency_symbol' => 'S$', 'currency_name' => 'Singapore Dollar'],
            ['code' => 'ZA', 'name' => 'South Africa', 'currency_code' => 'ZAR', 'currency_symbol' => 'R', 'currency_name' => 'South African Rand'],
            ['code' => 'ES', 'name' => 'Spain', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_name' => 'Euro'],
            ['code' => 'LK', 'name' => 'Sri Lanka', 'currency_code' => 'LKR', 'currency_symbol' => 'Rs', 'currency_name' => 'Sri Lankan Rupee'],
            ['code' => 'SD', 'name' => 'Sudan', 'currency_code' => 'SDG', 'currency_symbol' => 'ج.س.', 'currency_name' => 'Sudanese Pound'],
            ['code' => 'SE', 'name' => 'Sweden', 'currency_code' => 'SEK', 'currency_symbol' => 'kr', 'currency_name' => 'Swedish Krona'],
            ['code' => 'CH', 'name' => 'Switzerland', 'currency_code' => 'CHF', 'currency_symbol' => 'CHF', 'currency_name' => 'Swiss Franc'],
            ['code' => 'TW', 'name' => 'Taiwan', 'currency_code' => 'TWD', 'currency_symbol' => 'NT$', 'currency_name' => 'New Taiwan Dollar'],
            ['code' => 'TZ', 'name' => 'Tanzania', 'currency_code' => 'TZS', 'currency_symbol' => 'TSh', 'currency_name' => 'Tanzanian Shilling'],
            ['code' => 'TH', 'name' => 'Thailand', 'currency_code' => 'THB', 'currency_symbol' => '฿', 'currency_name' => 'Thai Baht'],
            ['code' => 'TR', 'name' => 'Turkey', 'currency_code' => 'TRY', 'currency_symbol' => '₺', 'currency_name' => 'Turkish Lira'],
            ['code' => 'UG', 'name' => 'Uganda', 'currency_code' => 'UGX', 'currency_symbol' => 'USh', 'currency_name' => 'Ugandan Shilling'],
            ['code' => 'UA', 'name' => 'Ukraine', 'currency_code' => 'UAH', 'currency_symbol' => '₴', 'currency_name' => 'Ukrainian Hryvnia'],
            ['code' => 'AE', 'name' => 'United Arab Emirates', 'currency_code' => 'AED', 'currency_symbol' => 'د.إ', 'currency_name' => 'UAE Dirham'],
            ['code' => 'GB', 'name' => 'United Kingdom', 'currency_code' => 'GBP', 'currency_symbol' => '£', 'currency_name' => 'British Pound'],
            ['code' => 'US', 'name' => 'United States', 'currency_code' => 'USD', 'currency_symbol' => '$', 'currency_name' => 'US Dollar'],
            ['code' => 'UY', 'name' => 'Uruguay', 'currency_code' => 'UYU', 'currency_symbol' => '$U', 'currency_name' => 'Uruguayan Peso'],
            ['code' => 'UZ', 'name' => 'Uzbekistan', 'currency_code' => 'UZS', 'currency_symbol' => 'сўм', 'currency_name' => 'Uzbekistani Som'],
            ['code' => 'VE', 'name' => 'Venezuela', 'currency_code' => 'VES', 'currency_symbol' => 'Bs.S', 'currency_name' => 'Venezuelan Bolívar'],
            ['code' => 'VN', 'name' => 'Vietnam', 'currency_code' => 'VND', 'currency_symbol' => '₫', 'currency_name' => 'Vietnamese Dong'],
            ['code' => 'YE', 'name' => 'Yemen', 'currency_code' => 'YER', 'currency_symbol' => '﷼', 'currency_name' => 'Yemeni Rial'],
            ['code' => 'ZM', 'name' => 'Zambia', 'currency_code' => 'ZMW', 'currency_symbol' => 'ZK', 'currency_name' => 'Zambian Kwacha'],
            ['code' => 'ZW', 'name' => 'Zimbabwe', 'currency_code' => 'ZWL', 'currency_symbol' => 'Z$', 'currency_name' => 'Zimbabwean Dollar'],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['code' => $country['code']],
                array_merge($country, [
                    'flag_emoji' => self::codeToFlag($country['code']),
                ]),
            );
        }
    }

    private static function codeToFlag(string $code): string
    {
        $code = strtoupper($code);

        return mb_chr(0x1F1E6 + ord($code[0]) - ord('A'))
             . mb_chr(0x1F1E6 + ord($code[1]) - ord('A'));
    }
}
