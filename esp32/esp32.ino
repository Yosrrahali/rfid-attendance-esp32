#include <SPI.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>

// ==========================================
// CONFIGURATION
// ==========================================
const char* ssid = "yuyou";
const char* password = "you_2005";
const char* serverIP = "10.172.136.40"; // CHANGE AVEC TON IP
const char* projectFolder = "clevergate";
// ==========================================

#define SS_PIN 9
#define RST_PIN 15
#define SPI_SCK 18
#define SPI_MOSI 4
#define SPI_MISO 5

#define RED_LED 12    // Inconnu
#define GREEN_LED 11  // Présent (1er scan du jour)
#define BLUE_LED 10   // Déjà passé (2ème scan ou plus)

MFRC522 rfid(SS_PIN, RST_PIN);

void blinkLED(int ledPin, int times, int delayTime) {
  for (int i = 0; i < times; i++) {
    digitalWrite(ledPin, HIGH);
    delay(delayTime);
    digitalWrite(ledPin, LOW);
    delay(delayTime);
  }
}

void setup() {
  Serial.begin(115200);

  pinMode(RED_LED, OUTPUT);
  pinMode(GREEN_LED, OUTPUT);
  pinMode(BLUE_LED, OUTPUT);
  digitalWrite(RED_LED, LOW);
  digitalWrite(GREEN_LED, LOW);
  digitalWrite(BLUE_LED, LOW);

  WiFi.begin(ssid, password);
  Serial.print("Connecting to Wi-Fi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWi-Fi connected!");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());

  SPI.begin(SPI_SCK, SPI_MISO, SPI_MOSI, SS_PIN);
  rfid.PCD_Init();
  Serial.println("Systeme de Presence Pret...");
}

void loop() {
  if (!rfid.PICC_IsNewCardPresent()) return;
  if (!rfid.PICC_ReadCardSerial()) return;

  String readUID = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10) readUID += "0";
    readUID += String(rfid.uid.uidByte[i], HEX);
  }
  readUID.toUpperCase();
  Serial.print("Scanned UID: ");
  Serial.println(readUID);

  digitalWrite(RED_LED, LOW);
  digitalWrite(GREEN_LED, LOW);
  digitalWrite(BLUE_LED, LOW);

  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;

    // 1. Envoyer au site pour le formulaire (Scan simple)
    String urlRegister = "http://" + String(serverIP) + "/" + String(projectFolder) + "/api.php?action=receive_scan&uid=" + readUID;
    http.begin(urlRegister);
    http.GET(); 
    http.end();

    // 2. Vérifier la présence
    String urlVerify = "http://" + String(serverIP) + "/" + String(projectFolder) + "/api.php?action=verify&uid=" + readUID;
    
    http.begin(urlVerify);
    int httpCode = http.GET();

    if (httpCode > 0) {
      String payload = http.getString();
      Serial.println("Server Response: " + payload);

      // Logique des couleurs basée sur le "message" du JSON
      
      // Si c'est PRESENT (Premier scan du jour) -> VERT
      if (payload.indexOf("\"message\":\"present\"") >= 0) {
        Serial.println("PRESENT (Premier scan)");
        blinkLED(GREEN_LED, 3, 200);
      }
      // Si c'est DEJA PRESENT (Déjà scanné aujourd'hui) -> BLEU
      else if (payload.indexOf("\"message\":\"deja_present\"") >= 0) {
        Serial.println("DEJA PRESENT (Autre scan)");
        blinkLED(BLUE_LED, 3, 200);
      }
      // Si c'est INCONNU -> ROUGE
      else if (payload.indexOf("\"message\":\"inconnu\"") >= 0) {
        Serial.println("INCONNU");
        blinkLED(RED_LED, 3, 200);
      }
      else {
        // Cas par défaut ou erreur format
        Serial.println("Format reponse inattendu");
        blinkLED(RED_LED, 1, 500);
      }

    } else {
      Serial.print("HTTP error: ");
      Serial.println(httpCode);
      blinkLED(RED_LED, 5, 100); // Erreur de connexion au serveur
    }

    http.end();
  } else {
    Serial.println("Wi-Fi disconnected!");
    blinkLED(RED_LED, 5, 100); // Erreur Wifi
  }

  rfid.PICC_HaltA();
  delay(2000);
}
