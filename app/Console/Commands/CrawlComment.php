<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\Product;
use App\Services\Crawler\CrawlerCurlInit;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CrawlComment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:comment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl comment of products';

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

//        Comment::truncate();
        $lastComment = DB::table('comments')->latest('id')->first();
        $productID = $lastComment->product_id ?? 1;
        $products = Product::selectRaw('min(id) as id, name, url, max(reviews) as reviews, max(shop_id) as shop_id')
            ->where('id', '>=', $productID)
            ->groupBy('name', 'url')
            ->orderBy('id', 'ASC')
            ->get();

        foreach ($products as $product) {
            echo $product->name . "\n";
            $productUrl = explode('.', $product->url);
            $itemId = intval(array_pop($productUrl));

            $offset = 0;
            while ($offset <= $product->reviews) {
                $response = $this->getCommentApi($product->shop->shop_id, $itemId, $offset, $ch);
                $data = Arr::get($response['data'], 'ratings');

                if ($data) {
                    foreach ($data as $item) {
                        if ($item['comment']) {
                            echo $item['author_username'];
                            echo "\n";
                            $time = date('Y-m-d h:i:s', $item['mtime']);

                            Comment::updateOrCreate([
                                'product_id' => $product->id,
                                'author' => $item['author_username'],
                                'time' => $time,
                            ], [
                                'product_id' => $product->id,
                                'author' => $item['author_username'],
                                'rating' => $item['rating_star'],
                                'content' => $item['comment'],
                                'time' => $time,
                            ]);
                        } else {
                            echo "Comment is null!!!!!!!!!!!!!!!!!!!!!!!!";
                            echo "\n";
                        }
                    }
                } else {
                    break;
                }
                $offset += 6;
            }
        }
        return 0;
    }

    function getCommentApi($shopId, $itemId, $offset, $ch)
    {
        curl_setopt($ch, CURLOPT_PROXY, '');
        curl_setopt($ch, CURLOPT_POST, 0);
        echo "https://shopee.vn/api/v2/item/get_ratings?filter=0&flag=1&itemid="
            . $itemId . "&limit=6&offset=" . $offset . "&shopid=" . $shopId . "&type=0";
        echo "\n";
        curl_setopt($ch, CURLOPT_URL, "https://shopee.vn/api/v2/item/get_ratings?filter=0&flag=1&itemid="
            . $itemId . "&limit=6&offset=" . $offset . "&shopid=" . $shopId . "&type=0");
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);

        return (json_decode($body, true));
    }
}
