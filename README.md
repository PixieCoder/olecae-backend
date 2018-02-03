# Olecae backend

The backend component of Pixie Coder's multi-player
RPG.

## Running

To run this in a development environment, please refer
to the dev-environment project. This project should be
pulled in as a subproject of that one.

## What it does

This project is meant to set up a PHP-based WebSocket
server that coordinates the clients, sends them world
data, and confirms/disallows their actions in the
game world.

Some components (eg generating a game world from
a random seed) are duplicated in backend and frontend.
This is to simultaneously provide speed and hold
bandwidth consumption down. We don't trust the 
frontend to take care of truth, but we have to give
it as much independence as possible.

## License

This project is (C) by *Pixie Coder AB* 2018
