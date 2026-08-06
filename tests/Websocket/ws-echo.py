import asyncio
import websockets
import sys

async def echo_client(port):
    uri = f"ws://127.0.0.1:{port}"
    try:
        async with websockets.connect(uri) as websocket:
            async for message in websocket:
                if message == "stop":
                    break
                await websocket.send("echo: " + message)
    except Exception as e:
        sys.exit(1)

if __name__ == "__main__":
    port = sys.argv[1] if len(sys.argv) > 1 else 8080
    asyncio.run(echo_client(port))
