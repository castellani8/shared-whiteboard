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
        >
            <canvas
                ref="canvas"
                width="800"
                height="600"
                @mousedown="startDrawing"
                @mousemove="draw"
                @mouseup="stopDrawing"
                @mouseleave="stopDrawing"
                @touchstart="handleTouch"
                @touchmove="handleTouch"
                @touchend="stopDrawing"
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
            lastTimestamp: 0,
            minDistance: 2,
            throttleTime: 16,
            currentStrokeId: null,
        };
    },
    mounted() {
        this.initializeCanvas();
        this.initializePusher();

        if (!localStorage.getItem('token')) {
            localStorage.setItem('token', Math.random().toString(36).slice(2));
        }
    },
    methods: {
        initializeCanvas() {
            this.ctx = this.$refs.canvas.getContext('2d', { willReadFrequently: true });
            this.ctx.lineJoin = 'round';
            this.ctx.lineCap = 'round';
            this.ctx.strokeStyle = this.currentColor;
            this.ctx.lineWidth = this.lineWidth;
        },

        initializePusher() {
            const pusher = new Pusher('8fa1524299d089e61476', {
                cluster: 'mt1'
            });
            const channel = pusher.subscribe('whiteboard');
            channel.bind('drawing.updated', (data) => {
                if(data.token !== localStorage.getItem('token')) {
                    this.handleRemoteDrawing(data);
                }
            });
            channel.bind('stroke.end', (data) => {
                if(data.token !== localStorage.getItem('token')) {
                    this.handleRemoteStrokeEnd(data);
                }
            });
        },

        generateStrokeId() {
            return `${localStorage.getItem('token')}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
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

            return { x, y, timestamp: Date.now() };
        },

        handleTouch(event) {
            event.preventDefault();
            if (event.type === 'touchstart') {
                this.startDrawing(event);
            } else if (event.type === 'touchmove') {
                this.draw(event);
            }
        },

        startDrawing(event) {
            this.drawing = true;
            this.currentStrokeId = this.generateStrokeId();
            const point = this.getPoint(event);
            this.points = [point];
            this.broadcastPoint(point, true);
        },

        draw(event) {
            if (!this.drawing) return;

            const now = Date.now();
            if (now - this.lastTimestamp < this.throttleTime) return;

            const newPoint = this.getPoint(event);
            const lastPoint = this.points[this.points.length - 1];

            const distance = Math.sqrt(
                Math.pow(newPoint.x - lastPoint.x, 2) +
                Math.pow(newPoint.y - lastPoint.y, 2)
            );

            if (distance < this.minDistance) return;

            this.points.push(newPoint);
            this.drawCurve();
            this.broadcastPoint(newPoint, false);

            this.lastTimestamp = now;
        },

        drawCurve() {
            if (this.points.length < 2) return;

            const points = this.points;
            const ctx = this.ctx;

            ctx.beginPath();
            ctx.moveTo(points[0].x, points[0].y);

            if (points.length === 2) {
                ctx.lineTo(points[1].x, points[1].y);
            } else {
                for (let i = 1; i < points.length - 2; i++) {
                    const xc = (points[i].x + points[i + 1].x) / 2;
                    const yc = (points[i].y + points[i + 1].y) / 2;
                    ctx.quadraticCurveTo(points[i].x, points[i].y, xc, yc);
                }

                ctx.quadraticCurveTo(
                    points[points.length - 2].x,
                    points[points.length - 2].y,
                    points[points.length - 1].x,
                    points[points.length - 1].y
                );
            }

            ctx.stroke();
        },

        handleRemoteDrawing(data) {
            if (!this.remoteStrokes.has(data.strokeId)) {
                this.remoteStrokes.set(data.strokeId, {
                    points: [],
                    color: data.color,
                    width: data.width,
                    active: true
                });
            }

            const stroke = this.remoteStrokes.get(data.strokeId);
            if (!stroke.active) return;

            if (data.isNewStroke) {
                stroke.points = [{ x: data.x, y: data.y, timestamp: Date.now() }];
            } else {
                stroke.points.push({ x: data.x, y: data.y, timestamp: Date.now() });

                this.ctx.save();
                this.ctx.strokeStyle = stroke.color;
                this.ctx.lineWidth = stroke.width;

                this.drawRemoteCurve(stroke.points);

                this.ctx.restore();
            }
        },

        handleRemoteStrokeEnd(data) {
            const stroke = this.remoteStrokes.get(data.strokeId);
            if (stroke) {
                stroke.active = false;
                if (stroke.points.length > 1) {
                    this.ctx.save();
                    this.ctx.strokeStyle = stroke.color;
                    this.ctx.lineWidth = stroke.width;
                    this.drawRemoteCurve(stroke.points);
                    this.ctx.restore();
                }
            }
        },

        drawRemoteCurve(points) {
            if (points.length < 2) return;

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

        stopDrawing() {
            if (!this.drawing) return;

            axios.post('/api/stroke-end', {
                token: localStorage.getItem('token'),
                strokeId: this.currentStrokeId
            }).catch(error => {
                console.error('Error sending stroke end:', error);
            });

            this.drawing = false;
            this.points = [];
            this.lastTimestamp = 0;
            this.currentStrokeId = null;
        },

        broadcastPoint(point, isNewStroke) {
            axios.post('/api/drawing', {
                x: point.x,
                y: point.y,
                token: localStorage.getItem('token'),
                strokeId: this.currentStrokeId,
                color: this.currentColor,
                width: this.lineWidth,
                isNewStroke,
                timestamp: point.timestamp
            }).catch(error => {
                console.error('Error broadcasting point:', error);
            });
        },

        clearCanvas() {
            this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
            this.remoteStrokes.clear();
            this.points = [];
            axios.post('/api/clear-whiteboard').catch(error => {
                console.error('Error clearing whiteboard:', error);
            });
        }
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
