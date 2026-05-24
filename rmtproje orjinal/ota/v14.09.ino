#include <SPI.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <DHT.h>
#include <WiFiManager.h>
#include <HTTPClient.h>
#include <Update.h>
#include <EEPROM.h>

#define OLED_RESET -1
#define SCREEN_ADDRESS 0x3C
#define DHTPIN 17
#define DHTTYPE DHT22
#define BUTTON_PIN 13
#define NUM_READINGS 5

// OTA kontrol adresleri
const char* versionUrl = "https://www.rmtproje.com/ota/version.txt";
const char* firmwareUrl = "https://www.rmtproje.com/ota/firmware.ino.esp32.bin";

// Ota güncelleme verileri
float mevcutVersiyon = 1.0;
unsigned long oncekiZaman = 0;
const long kontrolAraligi = 60000;
int i;
int nansay;

// Display tanımlaması - diğer global değişkenlerden önce
Adafruit_SSD1306 display(128, 64, &Wire, OLED_RESET);

// Versiyonu EEPROM'dan oku
void versiyonOku() {
  EEPROM.begin(8);
  mevcutVersiyon = EEPROM.readFloat(0);
  if (isnan(mevcutVersiyon) || mevcutVersiyon < 1.0) {
    mevcutVersiyon = 1.0;
    EEPROM.writeFloat(0, mevcutVersiyon);
    EEPROM.commit();
  }
}

// Yeni versiyonu EEPROM'a kaydet
void versiyonKaydet(float yeniVersiyon) {
  EEPROM.writeFloat(0, yeniVersiyon);
  EEPROM.commit();
  mevcutVersiyon = yeniVersiyon;
}

void guncellemeyiKontrolEt() {
  HTTPClient http;

  Serial.println("\n------- Güncelleme Kontrolü -------");
  Serial.print("Mevcut Versiyon: v");
  Serial.println(mevcutVersiyon, 2);

  http.begin(versionUrl);
  int httpKodu = http.GET();

  if (httpKodu == HTTP_CODE_OK) {
    String yeniVersiyon = http.getString();
    yeniVersiyon.trim();
    float yeniVersiyonNo = yeniVersiyon.toFloat();

    Serial.print("Sunucu Versiyon: v");
    Serial.println(yeniVersiyonNo, 2);

    if (yeniVersiyonNo > mevcutVersiyon) {
      Serial.println(">> Yeni sürüm bulundu! Güncelleme başlıyor...");
      // Güncelleme bulundu ekranı
      display.clearDisplay();
      display.setTextSize(1);
      display.setTextColor(SSD1306_WHITE);
      display.setCursor(0, 0);
      display.println("Yeni surum bulundu!");
      display.print("Mevcut: v");
      display.println(mevcutVersiyon);
      display.print("Yeni: v");
      display.println(yeniVersiyonNo);
      display.display();
      delay(2000);
      http.end();
      http.begin(firmwareUrl);
      httpKodu = http.GET();

      if (httpKodu == HTTP_CODE_OK) {
        int dosyaBoyutu = http.getSize();
        if (dosyaBoyutu > 0) {
          Serial.println(">> Firmware indiriliyor...");
          // İndirme başladı ekranı
          display.clearDisplay();
          display.setCursor(0, 0);
          display.println("Guncelleme");
          display.println("indiriliyor...");
          display.display();
          if (Update.begin(dosyaBoyutu)) {
            WiFiClient * stream = http.getStreamPtr();
            size_t yazilanBoyut = Update.writeStream(*stream);

            if (yazilanBoyut == dosyaBoyutu) {
              if (Update.end()) {
                if (Update.isFinished()) {
                  Serial.println(">> Güncelleme başarılı!");
                  // Yükleme işlemleri...
                  if (Update.isFinished()) {
                    display.clearDisplay();
                    display.setCursor(0, 0);
                    display.println("Guncelleme");
                    display.println("basarili!");
                    display.println("Yeniden");
                    display.println("baslatiliyor...");
                    display.display();
                    delay(2000);
                  }
                  Serial.printf(">> v%.2f -> v%.2f\n", mevcutVersiyon, yeniVersiyonNo);
                  versiyonKaydet(yeniVersiyonNo);
                  Serial.println(">> Sistem yeniden başlatılıyor...");
                  yenidenbaslat();
                  // ESP.restart();
                }
              }
            }
          }
        }
      }
    } else {
      Serial.println(">> Sistem güncel!");
    }
  }
  Serial.println("----------------------------------");
  http.end();
  i = 0;
}

// int sayma;
uint32_t chipId = 0;
boolean wifidurum1 = false;
unsigned long buttonPressStartTime = 0;
bool buttonPressed = false;
bool buyuk_ekran ;

// En üstte global olarak tanımlama yapıyoruz
extern Adafruit_SSD1306 display;

// Setup öncesinde display nesnesini oluşturuyoruz
// Adafruit_SSD1306 display(128, 64, &Wire, OLED_RESET);

DHT dht(DHTPIN, DHTTYPE);
WiFiManager wm;

//const int potpin = 0;

// Ortalama için veriler
float tempReadings[NUM_READINGS];
float humReadings[NUM_READINGS];
int readIndex = 0;
float tempTotal = 0;
float humTotal = 0;
unsigned long lastMeasurementTime = 0;
const unsigned long interval = 60000;
int post_say;
int kod1;

// WiFiManager parametreleri
WiFiManagerParameter custom_ip("ip", "Statik IP", "192.168.1.200", 15);
WiFiManagerParameter custom_gateway("gateway", "Gateway", "192.168.1.1", 15);
WiFiManagerParameter custom_subnet("subnet", "Subnet", "255.255.255.0", 15);
WiFiManagerParameter custom_dns("dns", "DNS", "8.8.8.8", 15);
WiFiManagerParameter custom_dhcp("dhcp", "DHCP (1=Açık/0=Kapalı)", "1", 1);

int getWiFiStrength() { //wifi sinyal kalitesi
  long rssi = WiFi.RSSI();
  // RSSI değerini yüzdelik değere dönüştür (-100dBm -> 0%, -50dBm -> 100%)
  int quality = 2 * (rssi + 100);

  if (quality > 100)
    quality = 100;
  if (quality < 0)
    quality = 0;

  return quality;
}

bool detectDisplaySize() {  //ekran boyutunu yakalama
  Wire.beginTransmission(SCREEN_ADDRESS);
  bool error = Wire.endTransmission();

  if (!error) {
    // Try 128x32 first
    display = Adafruit_SSD1306(128, 32, &Wire, OLED_RESET);
    if (display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
      Wire.beginTransmission(SCREEN_ADDRESS);
      Wire.write(0x00);
      Wire.write(0xDA);
      Wire.endTransmission();

      Wire.requestFrom(SCREEN_ADDRESS, 1);
      if (Wire.available()) {
        uint8_t com_pins = Wire.read();
        if (com_pins == 0x02) {
          buyuk_ekran = false;
          Serial.println("128x32 OLED detected");
          // Ekranı yeniden başlat
          display = Adafruit_SSD1306(128, 32, &Wire, OLED_RESET);
          display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS);
          display.clearDisplay();
          display.display();
          return true;
        }
      }
    }

    display = Adafruit_SSD1306(128, 64, &Wire, OLED_RESET);
    if (display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
      buyuk_ekran = true;
      Serial.println("128x64 OLED detected");
      display.clearDisplay();
      display.display();
      return true;
    }
  }
  return false;
}

int rssiYuzdeHesapla(int rssi) {
  // RSSI değerini yüzdeye çevirme (-100 dBm = %0, -50 dBm = %100)
  int yuzde = 2 * (rssi + 100);
  if (yuzde > 100) yuzde = 100;
  if (yuzde < 0) yuzde = 0;
  return yuzde;
}


void setup() {
  Serial.begin(9600);

  versiyonOku(); // EEPROM'dan versiyon bilgisini oku

  Wire.begin();

  chipId = (ESP.getEfuseMac() >> 12) % 0xFFFFFFF;

  if (!detectDisplaySize()) {
    Serial.println(F("OLED ekran bulunamadı!"));
    for (;;);
  }

  wm.addParameter(&custom_ip);
  wm.addParameter(&custom_gateway);
  wm.addParameter(&custom_subnet);
  wm.addParameter(&custom_dns);
  wm.addParameter(&custom_dhcp);

  String hostname = "HK-" + String(chipId, HEX);
  WiFi.setHostname(hostname.c_str());
  if (buyuk_ekran == true) {
    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);
    display.setCursor(1, 5);
    display.println("SSID: Havakontrol");
    display.setCursor(1, 15);
    display.println("Sifre: 123456789");
    display.setCursor(1, 25);
    display.print("Kimlik: ");
    display.println(String(chipId, HEX));
    display.display();
  }
  if (buyuk_ekran == false) {
    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);
    display.setCursor(1, 5);
    display.println("SSID: Havakontrol");
    display.setCursor(1, 15);
    display.println("Sifre: 123456789");
    display.setCursor(1, 25);
    display.print("Kimlik: ");
    display.println(String(chipId, HEX));
    display.display();
  }

  bool res = wm.autoConnect("Havakontrol", "123456789");

  if (!res) {
    Serial.println("Bağlantı başarısız");
    wifidurum1 = true;
  } else {
    if (String(custom_dhcp.getValue()) == "0") {
      IPAddress ip, gateway, subnet, dns;
      ip.fromString(custom_ip.getValue());
      gateway.fromString(custom_gateway.getValue());
      subnet.fromString(custom_subnet.getValue());
      dns.fromString(custom_dns.getValue());
      WiFi.config(ip, gateway, subnet, dns);
    }
    Serial.println("Bağlandı");
    wifidurum1 = false;
  }

  pinMode(BUTTON_PIN, INPUT_PULLUP);

  dht.begin();
  for (int i = 0; i < NUM_READINGS; i++) {
    tempReadings[i] = 0;
    humReadings[i] = 0;
  }

}

void loop() {

  unsigned long simdikiZaman = millis(); //güncelleme periyodu sayma

  if (simdikiZaman - oncekiZaman >= kontrolAraligi) {
    oncekiZaman = simdikiZaman;
    if (WiFi.status() == WL_CONNECTED) {
      Serial.println("Güncelleme kontrolü yapılıyor...");
      guncellemeyiKontrolEt();
    }
  }

  // Sensör verilerini oku (her döngüde)
  float newHumidity = dht.readHumidity();
  float newTemperature = dht.readTemperature();

  // Eski veriyi toplamdan çıkar ve yeni veriyi ekle
  tempTotal = tempTotal - tempReadings[readIndex] + newTemperature;
  humTotal = humTotal - humReadings[readIndex] + newHumidity;

  // Yeni veriyi dizilere ekle
  tempReadings[readIndex] = newTemperature;
  humReadings[readIndex] = newHumidity;

  // Sıradaki indise geç
  readIndex = (readIndex + 1) % NUM_READINGS;

  // Ortalama hesapla
  float averageTemp = tempTotal / NUM_READINGS;
  float averageHum = humTotal / NUM_READINGS;



  getWiFiStrength();

  // WiFi durumunu kontrol et
  if (WiFi.isConnected()) {
    display.clearDisplay();
    if (buyuk_ekran == true) {
      display.setTextSize(1);
      display.setTextColor(SSD1306_WHITE);

      display.setCursor(60, 1);
      display.print("WiFi: %");
      display.println(getWiFiStrength());

      display.setTextSize(2);
      display.setTextColor(SSD1306_WHITE);
      display.setCursor(20, 10);
      display.println(String(averageTemp) + " C");
      display.setCursor(20, 25);
      display.println(String(averageHum) + " %");

      display.setCursor(0, 50);

      display.setTextSize(1);
      display.print("ID: ");
      display.print(String(chipId, HEX));
      display.setCursor(122, 50);
      display.println(kod1);
    }
    if (buyuk_ekran == false) {
      display.setTextSize(2);
      display.setTextColor(SSD1306_WHITE);
      display.setCursor(00, 5);
      display.print(String(averageTemp, 1)) ;
      display.setTextSize(1);
      display.println( "C  ");
      display.setCursor(71, 5);
      display.setTextSize(2);
      display.print(String(averageHum, 1));
      display.setTextSize(1);
      display.println("%");
      display.setCursor(0, 25);
      display.setTextSize(1);
      display.print("Kimlik: ");
      display.print(String(chipId, HEX));
      display.print("  -  ");
      display.println(kod1);
    }

    unsigned long currentMillis = millis();



    if (currentMillis - lastMeasurementTime >= interval) {

      if (post_say == 60) {
        yenidenbaslat();
      }

      int rssi = WiFi.RSSI();
      int sinyalYuzdesi = rssiYuzdeHesapla(rssi);
      //  int sinyalYuzdesi = currentMillis - lastMeasurementTime;
      Serial.print("web ");
      Serial.print(mevcutVersiyon, 2);
      Serial.print(" & ");
      Serial.print(i);
      Serial.print(" WiFi: %");
      Serial.println(sinyalYuzdesi);

      kod1 = random(1, 9);

      lastMeasurementTime = currentMillis;

      if (String(averageTemp) == "NaN" || String(averageHum) == "NaN") {
        nansay++;
        if (nansay == 3) {
          yenidenbaslat();
        }
      } else {
        nansay = 0;
      }

      HTTPClient http;

      String serino = String(chipId, HEX);
      String postData = "serino=" + serino + "&temp=" + String(averageTemp) + "&hum=" + String(averageHum) + "&wifi=" + sinyalYuzdesi + "&versiyon=" + String(mevcutVersiyon, 2) + "&oturum=" + post_say + "&kod1dk=" + kod1 ;

      http.begin("https://havakontrol.com/dash/verigir.php");
      http.addHeader("Content-Type", "application/x-www-form-urlencoded");

      int httpResponseCode = http.POST(postData);

      if (httpResponseCode > 0) {
        String response = http.getString();
        Serial.println(httpResponseCode);
        Serial.println(response);
      } else {
        Serial.print("Error on sending POST: ");
        Serial.println(httpResponseCode);
      }
      http.end();
      post_say++;
    }
  } else {
    display.clearDisplay();
    display.setTextSize(1);
    display.setCursor(0, 0);
    display.println("WiFi Baglantisi Yok");
    display.display();
  }
  display.display();
}

int kalanzaman;

void yenidenbaslat() {
  //Serial.println("Yeniden Başlıyor!");
  display.clearDisplay();

  for (int i = 9; i > 0; i--) {
    display.clearDisplay();
    display.setTextColor(SSD1306_WHITE);
    display.setTextSize(1);
    display.setCursor(15, 5);
    display.println("Yeniden baslatmaya");
    display.setTextSize(2);
    display.setCursor(35, 15);
    display.println(String(i) + " Sn.");
    display.display();
    delay(1000);
  }

  ESP.restart();
}
