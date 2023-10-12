<?php

namespace App\Console\Commands;

use App\Models\ShopeeCategory;
use App\Services\Crawler\CrawlerCurlInit;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class CrawlCategory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:category';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl list category in Shopee';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(CrawlerCurlInit $crawlerCurlInit)
    {
        $ch = $crawlerCurlInit->init();

//        ShopeeCategory::truncate();
        $response = $this->getCategoryApi($ch);
        $data = Arr::get($response['data'], 'category_list');

        foreach ($data as $item) {
            if ($item['catid'] === 91) {
                return 0;
            }
            ShopeeCategory::create([
                'cate_id' => $item['catid'],
                'name' => $item['display_name'],
                'url' => "https://shopee.vn/mall/brands/" . $item['catid'],
            ]);
        }

        echo "Crawl category success!!!";
        echo "\n";

        return 0;
    }

    function getCategoryApi($ch)
    {
        curl_setopt($ch, CURLOPT_PROXY, '');
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, "https://shopee.vn/api/v2/category_list/get");
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);

        return (json_decode($body, true));
    }
}
