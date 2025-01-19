<template>
    <div class="whiteboard-container">
        <h2>Whiteboard</h2>
        <div class="controls">
            <input type="color" v-model="currentColor" title="Choose color">
            <input type="range" v-model="lineWidth" min="1" max="10" title="Line width">
            <button @click="clearCanvas" class="clear-btn">Clear</button>
        </div>
        <div
            ref="whiteboard"
            class="whiteboard"
            @mouseleave="handleMouseLeave"
        >
            <canvas
                ref="canvas"
                width="800"
                height="600"
                @mousedown="startDrawing"
                @mousemove="draw"
                @mouseup="stopDrawing"
                @touchstart.prevent="handleTouch"
                @touchmove.prevent="handleTouch"
                @touchend.prevent="stopDrawing"
                @touchcancel.prevent="stopDrawing"
            ></canvas>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'Whiteboard',
    data() {
        return {
            drawing: false,
            ctx: null,
            currentColor: '#000000',
            lineWidth: 2,
            points: [],
            remoteStrokes: new Map(),
            currentStrokeId: null,
            minDistance: 2,
            incomingStrokes: new Map(),
            isMouseDown: false,
            token: Math.random().toString(36).slice(2)
        };
    },
    mounted() {
        this.initializeCanvas();
        this.initializePusher();
        this.loadWhiteboardState();
    },
    methods: {
        initializeCanvas() {
            this.ctx = this.$refs.canvas.getContext('2d', {willReadFrequently: true});
            this.ctx.lineJoin = 'round';
            this.ctx.lineCap = 'round';
            this.ctx.strokeStyle = this.currentColor;
            this.ctx.lineWidth = this.lineWidth;
        },

        initializePusher() {
            const pusher = new Pusher('8fa1524299d089e61476', {
                cluster: 'mt1'
            });
            const channel = pusher.subscribe(`whiteboard.${this.$route.params.pass}`);

            channel.bind('stroke.chunk', (data) => {
                if (data.token !== this.token) {
                    this.handleRemoteStrokeChunk(data);
                }
            });

            channel.bind('whiteboard.cleared', (data) => {
                if (data.token !==  this.token) {
                    this.performClear();
                }
            });
        },

        handleRemoteStrokeChunk(data) {
            const {strokeId, points, color, width, chunkIndex, totalChunks} = data;

            if (!this.incomingStrokes.has(strokeId)) {
                this.incomingStrokes.set(strokeId, {
                    chunks: new Array(totalChunks).fill(null),
                    color,
                    width,
                    receivedChunks: 0,
                });
            }

            const strokeData = this.incomingStrokes.get(strokeId);
            strokeData.chunks[chunkIndex] = points;
            strokeData.receivedChunks++;

            if (strokeData.receivedChunks === totalChunks) {
                const allPoints = strokeData.chunks.flat();

                this.ctx.save();
                this.ctx.strokeStyle = strokeData.color;
                this.ctx.lineWidth = strokeData.width;
                this.drawStroke(allPoints);
                this.ctx.restore();

                this.remoteStrokes.set(strokeId, {
                    points: allPoints,
                    color: strokeData.color,
                    width: strokeData.width,
                });

                this.incomingStrokes.delete(strokeId);
            }
        },

        handleMouseLeave() {
            if (this.drawing) {
                this.stopDrawing();
            }
            this.isMouseDown = false;
        },

        async loadWhiteboardState() {
            try {
                const response = await axios.get(`/api/whiteboard/${this.$route.params.pass}`);
                const strokes = response.data;

                this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
                this.remoteStrokes.clear();

                for (const [strokeId, strokeData] of Object.entries(strokes)) {
                    if (strokeData.points && Array.isArray(strokeData.points)) {
                        this.ctx.save();
                        this.ctx.strokeStyle = strokeData.color;
                        this.ctx.lineWidth = strokeData.width;
                        this.drawStroke(strokeData.points);
                        this.ctx.restore();

                        this.remoteStrokes.set(strokeId, {
                            points: strokeData.points,
                            color: strokeData.color,
                            width: strokeData.width,
                        });
                    }
                }
            } catch (error) {
                console.error('Error loading whiteboard state:', error);
            }
        },

        generateStrokeId() {
            return `${this.token}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        },

        getPoint(event) {
            const canvas = this.$refs.canvas;
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;

            let x, y;
            if (event.touches) {
                x = (event.touches[0].clientX - rect.left) * scaleX;
                y = (event.touches[0].clientY - rect.top) * scaleY;
            } else {
                x = (event.clientX - rect.left) * scaleX;
                y = (event.clientY - rect.top) * scaleY;
            }

            return {
                x,
                y,
                timestamp: Date.now(),
            };
        },

        handleTouch(event) {
            if (event.type === 'touchstart') {
                this.startDrawing(event);
            } else if (event.type === 'touchmove') {
                this.draw(event);
            }
        },

        startDrawing(event) {
            this.isMouseDown = true;
            this.drawing = true;
            this.currentStrokeId = this.generateStrokeId();
            const point = this.getPoint(event);
            this.points = [point];

            // Initialize the stroke style
            this.ctx.save();
            this.ctx.strokeStyle = this.currentColor;
            this.ctx.lineWidth = this.lineWidth;

            // Start a new path for this stroke
            this.ctx.beginPath();
            this.ctx.moveTo(point.x, point.y);
        },

        draw(event) {
            if (!this.drawing || !this.isMouseDown) return;

            const newPoint = this.getPoint(event);
            const lastPoint = this.points[this.points.length - 1];

            const distance = Math.sqrt(
                Math.pow(newPoint.x - lastPoint.x, 2) +
                Math.pow(newPoint.y - lastPoint.y, 2)
            );

            if (distance < this.minDistance) return;

            this.points.push(newPoint);

            // Draw only the new segment
            this.ctx.lineTo(newPoint.x, newPoint.y);
            this.ctx.stroke();
        },

        drawStroke(points) {
            if (!points || points.length < 2) return;

            this.ctx.beginPath();
            this.ctx.moveTo(points[0].x, points[0].y);

            if (points.length === 2) {
                this.ctx.lineTo(points[1].x, points[1].y);
            } else {
                for (let i = 1; i < points.length - 2; i++) {
                    const xc = (points[i].x + points[i + 1].x) / 2;
                    const yc = (points[i].y + points[i + 1].y) / 2;
                    this.ctx.quadraticCurveTo(points[i].x, points[i].y, xc, yc);
                }

                this.ctx.quadraticCurveTo(
                    points[points.length - 2].x,
                    points[points.length - 2].y,
                    points[points.length - 1].x,
                    points[points.length - 1].y
                );
            }

            this.ctx.stroke();
        },

        async stopDrawing() {
            if (!this.drawing) return;

            this.isMouseDown = false;
            this.drawing = false;
            this.ctx.restore();

            if (this.points.length >= 2) {
                try {
                    await axios.post('/api/stroke', {
                        strokeId: this.currentStrokeId,
                        token: this.token,
                        pass: this.$route.params.pass,
                        points: this.points,
                        color: this.currentColor,
                        width: this.lineWidth
                    });
                } catch (error) {
                    console.error('Error sending stroke:', error);
                }
            }

            this.points = [];
            this.currentStrokeId = null;
        },

        performClear() {
            this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
            this.remoteStrokes.clear();
            this.points = [];
            this.incomingStrokes.clear();
        },

        clearCanvas() {
            this.performClear();

            axios.post('/api/clear-whiteboard', {
                pass: this.$route.params.pass,
                token: this.token
            }).catch(error => {
                console.error('Error clearing whiteboard:', error);
            });
        },
    },
    watch: {
        currentColor(newColor) {
            if (this.ctx) {
                this.ctx.strokeStyle = newColor;
            }
        },
        lineWidth(newWidth) {
            if (this.ctx) {
                this.ctx.lineWidth = newWidth;
            }
        }
    }
};
</script>

<style scoped>
.whiteboard-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
}

.controls {
    margin-bottom: 1rem;
    display: flex;
    gap: 1rem;
    align-items: center;
}

.whiteboard {
    border: 2px solid #000;
    position: relative;
}

canvas {
    border: 1px solid #ccc;
    cursor: crosshair;
}

.clear-btn {
    padding: 0.5rem 1rem;
    background-color: #ff4444;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.clear-btn:hover {
    background-color: #cc0000;
}
</style>
