import asyncio
import websockets
import json
import sys

async def mock_extension():
    uri = "ws://127.0.0.1:8080"
    try:
        async with websockets.connect(uri) as websocket:
            print("Connected to server")
            
            async for message in websocket:
                print(f"Received: {message}")
                req = json.loads(message)
                cmd = req.get("cmd")
                
                if cmd == "navigate":
                    await websocket.send(json.dumps({}))
                elif cmd == "html":
                    await websocket.send(json.dumps({"html": "<html><body>mock</body></html>"}))
                elif cmd == "execute":
                    await websocket.send(json.dumps({"value": 42}))
                elif cmd == "select":
                    await websocket.send(json.dumps({"items": ["<div id='test'></div>"]}))
                else:
                    await websocket.send(json.dumps({"error_type": "unknown_command", "error_message": "Unknown command"}))
    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    asyncio.run(mock_extension())
