import googlemaps
import time
import datetime
import json
import MySQLdb
import urllib3

gmaps = googlemaps.Client(
    key='AIzaSyAjLEkR0M8kV4je7G6fowZVZE0vP4gVCTE',
    timeout=5,
    retry_timeout=5,
    retry_over_query_limit=True,
    queries_per_second=1
    )

urllib3.disable_warnings()
conn = MySQLdb.connect(
            'localhost',
            'root',
            'hoangminh99',
            'thesis-crawl',
            charset="utf8mb4",
            use_unicode=True,
        )
cursor = conn.cursor()

# cursor.execute("""DROP TABLE IF EXISTS shop_offline""")
# cursor.execute("""create table if not exists shop_offline(
#                 id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
#                 place_id VARCHAR(512) NOT NULL,
#                 name VARCHAR(512),
#                 city VARCHAR(512),
#                 address VARCHAR(512),
#                 phone_number VARCHAR(512),
#                 rating DOUBLE,
#                 user_rating INT,
#                 UNIQUE KEY(place_id)
#                 )""")
# # cursor.execute("""DROP TABLE IF EXISTS reviews""")
# cursor.execute("""create table if not exists reviews(
#                 id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
#                 place_id VARCHAR(512) NOT NULL,
#                 author VARCHAR(512),
#                 rating INT,
#                 content LONGTEXT,
#                 time TIMESTAMP,
#                 UNIQUE KEY(author, time)
#                 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci""")
# cursor.execute("""ALTER TABLE reviews MODIFY content LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci""")

def crawlMaps(searchString, city, lat, lng, nextPage=''):
    print('Crawling!!!')
    try:
        time.sleep(5)
        places_result = gmaps.places(query=searchString, page_token=nextPage, type='shopping_mall, store, shoe_store, clothing_store', location=(lat, lng), language='vi')
    except ApiError as e:
        print(e)
    else:
        for result in places_result['results']:
            place_id = result['place_id']
            fields = ['name', 'formatted_phone_number', 'formatted_address', 'rating', 'user_ratings_total', 'review']
            place_result = gmaps.place(place_id=result['place_id'], fields=fields, language='vi')

            # Crawl place
            place = place_result['result']

            name = place.get('name')
            address = place.get('formatted_address')
            phone_number = place.get('formatted_phone_number') or ''
            rating = place.get('rating') or 0
            user_rating_total = place.get('user_ratings_total') or 0
            print('Place ID: ', place_id)
            print('Name: ', name)
            print('City: ', city)
            print('Address: ', address)
            print('Rating: ', rating)
            print('User Rating total: ', user_rating_total)

            cursor.execute("""REPLACE INTO shop_offline (place_id, name, city, address, phone_number, rating, user_rating) VALUES (%s, %s, %s, %s, %s, %s, %s)""", (
                    place_id,
                    name,
                    city,
                    address,
                    phone_number,
                    rating,
                    user_rating_total,
            ))
            conn.commit()

            time.sleep(2)

            # Crawl reviews
            if place_result['result'].get('reviews') is not None:
                for review in place_result['result'].get('reviews'):
                    print('----------------')
                    timeReview = int(review['time'])
                    timeReview = datetime.datetime.fromtimestamp(timeReview)

                    cursor.execute("""REPLACE INTO reviews (shop_offline_id, author, rating, content, time) VALUES (%s, %s, %s, %s, %s)""", (
                                place_id,
                                review['author_name'],
                                review['rating'],
                                review['text'] or '',
                                timeReview,
                            ))
                    conn.commit()
                    print('Crawled Review!!!')
    time.sleep(5)

    try:
        nextPageToken = places_result['next_page_token']
    except KeyError as e:
        print('----------------------------------------------')
        print(e)
    else:
        print('----------------------------------------------')
        print('Next page ------------>')
        crawlMaps(searchString, city, lat=lat, lng=lng, nextPage=nextPageToken)

if __name__ == '__main__':
    file = open('./vn.json', "r")
    vietnam = json.loads(file.read())
    for city in vietnam:
        keywords = ['giay dep', 'sneakers', 'giay', 'sneaker']
        for keyword in keywords:
            print(city['city'])
            crawlMaps(keyword, city=city['admin_name'], lat=city['lat'], lng=city['lng'])
    file.close()
