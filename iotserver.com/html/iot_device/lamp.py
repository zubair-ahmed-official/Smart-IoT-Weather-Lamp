from sense_emu import SenseHat
from time import sleep
import requests

sense = SenseHat()
sense.clear()

state = "setup"
mode = "temp"
selected_day = 1
selected_month = 1
site_id = 1
predictions = {}
location_name = ""
current_field = 0

scroll_speed = 0.05

SERVER_URL = "http://iotserver.com/server/api/predict.php"

def handle_event(event):
    global selected_day, selected_month, site_id, state, current_field, mode

    if event.action != "pressed":
        return

    if state == "setup":
        if event.direction == "left":
            current_field = (current_field - 1) % 3
        elif event.direction == "right":
            current_field = (current_field + 1) % 3
        elif event.direction == "up":
            if current_field == 0:
                selected_day = (selected_day % 31) + 1
            elif current_field == 1:
                selected_month = (selected_month % 12) + 1
            elif current_field == 2:
                site_id = (site_id % 5) + 1
        elif event.direction == "down":
            if current_field == 0:
                selected_day = (selected_day - 2) % 31 + 1
            elif current_field == 1:
                selected_month = (selected_month - 2) % 12 + 1
            elif current_field == 2:
                site_id = (site_id - 2) % 5 + 1
        elif event.direction == "middle":
            state = "normal"
            request_prediction()

        show_setup_field()

    elif state == "normal":
        if event.direction == "middle":
            state = "setup"
            show_setup_field()
        elif event.direction in ["left", "right"]:
            mode = "humidity" if mode == "temp" else "temp"
            sense.show_message(mode.capitalize(), scroll_speed=scroll_speed)

def show_setup_field():
    fields = ["Day", "Month", "Site"]
    values = [selected_day, selected_month, site_id]
    text = f"{fields[current_field]}: {values[current_field]}"
    sense.show_message(text, scroll_speed=scroll_speed)

def request_prediction():
    global predictions, location_name
    try:
        payload = {
            "day": selected_day,
            "month": selected_month,
            "site_id": site_id
        }
        headers = {'Content-Type': 'application/json'}
        response = requests.post(SERVER_URL, json=payload, headers=headers)

        print(f"Raw response: {response.text}")

        if response.status_code == 200:
            data = response.json()
            if "prediction" in data:
                predictions = data["prediction"]
                location_name = data.get("location", "Unknown")
                print(f"Prediction received for {location_name}: {predictions}")
                sense.show_message(location_name, scroll_speed=scroll_speed)
            else:
                print("Malformed response, missing 'prediction'")
                sense.show_message("Bad Data", scroll_speed=scroll_speed)
        else:
            print("Server error: ", response.status_code)
            sense.show_message("Server Error", scroll_speed=scroll_speed)
    except Exception as e:
        print("Error:", e)
        sense.show_message("No Server", scroll_speed=scroll_speed)

def display_status():
    if not predictions:
        return

    temp = sense.get_temperature()
    hum = sense.get_humidity()

    if mode == "temp":
        min_val = predictions['min_temp']
        max_val = predictions['max_temp']
        if min_val <= temp <= max_val:
            sense.clear(0, 255, 0)  # Green
        else:
            sense.clear(255, 0, 0)  # Red
    else:
        min_val = predictions['min_humidity']
        max_val = predictions['max_humidity']
        if min_val <= hum <= max_val:
            sense.clear(0, 0, 255)  # Blue
        else:
            sense.clear(255, 255, 0)  # Yellow

sense.stick.direction_any = handle_event
sense.show_message("Press middle to begin", scroll_speed=scroll_speed)

while True:
    if state == "normal":
        display_status()
    sleep(1)
