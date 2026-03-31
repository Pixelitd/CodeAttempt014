# PlayerCollide
A PocketMine-MP plugin that enables player-to-player collision, applying knockback when players get too close to each other.

# Features
- **Player Collision**: Players within a configurable distance will collide.
- **Configurable Knockback**: Adjust the base knockback strength and a multiplier based on player speed.

# Default Config
``` yaml
# PlayerCollide Configuration

# The maximum distance (in blocks) between two players for collision to occur.
# Players closer than this distance will be considered for collision.
collision_distance: 0.8

# The base knockback strength applied when players collide.
# This is the minimum knockback applied.
knockback_strength: 0.1

# The multiplier for knockback strength based on player speed.
# If a player is moving fast, the knockback will be increased by this multiplier
# multiplied by the player's speed.
speed_knockback_multiplier: 0.5
```
- **collision_distance**: The maximum distance (in blocks) between two players for a collision to be registered. Players closer than this distance will experience knockback.
- **knockback_strength**: The base knockback strength applied to both players during a collision. This is the minimum knockback value.
- **speed_knockback_multiplier**: A multiplier that increases the knockback strength based on the player's movement speed. A higher value means faster-moving players will cause more knockback.

# Upcoming Features

- Currently none planned. You can contribute or suggest for new features.

# Additional Notes

- If you find bugs or want to give suggestions, please visit [here](https://github.com/AIPTU/PlayerCollide/issues).
- We accept all contributions! If you want to contribute, please make a pull request in [here](https://github.com/AIPTU/PlayerCollide/pulls).
- Icons made from [www.flaticon.com](https://www.flaticon.com)
