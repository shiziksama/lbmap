#include <iostream>
#include <string>

int main() {
    std::ios::sync_with_stdio(false); // для трохи швидшого вводу/виводу
    std::cin.tie(nullptr);

    bool inWay = false;
    std::string line;
    std::string wayBuffer;

    while (true) {
        if (!std::getline(std::cin, line)) {
            break; // кінець вводу
        }

        if (!inWay) {
            // Перевіряємо, чи починається блок <way ...>
            if (line.find("<way") != std::string::npos) {
                inWay = true;
                wayBuffer = line + "\n"; // починаємо накопичувати блок
            } else {
                // Якщо це не <way>, виводимо як є
                std::cout << line << "\n";
            }
        } else {
            // Ми всередині <way> ... </way>
            wayBuffer += line + "\n";

            // Перевіряємо, чи це кінець блоку </way>
            if (line.find("</way>") != std::string::npos) {
                // Якщо в буфері є "cycle", "foot" або "pedestrian" – виводимо блок
                if (wayBuffer.find("cycle") != std::string::npos ||
                    wayBuffer.find("foot") != std::string::npos ||
                    wayBuffer.find("track") != std::string::npos ||
                    wayBuffer.find("pedestrian") != std::string::npos)
                {
                    std::cout << wayBuffer;
                }
                // Скидаємо режим та буфер
                inWay = false;
                wayBuffer.clear();
            }
        }
    }

    return 0;
}
