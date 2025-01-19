<template>
    <div class="room-form">
        <h2 class="title">Enter the Room Name</h2>
        <label for="pass" class="label">Room Name:</label>
        <input
            type="text"
            name="pass"
            v-model="pass"
            :class="{'input-error': isInvalid}"
            placeholder="Enter a valid room name"
            @input="validatePass"
            class="input-field"
        />
        <span v-if="isInvalid" class="error-message">
            Room name is required, must be between 8 and 50 characters, and contain only valid characters (letters, numbers, hyphen, and underscore).
        </span>

        <!-- Apenas o router-link -->
        <router-link v-if="isValid" :to="{ path: '/whiteboard/' + pass }" class="enter-button" append>
            Enter
        </router-link>
    </div>
</template>


<script>
export default {
    data() {
        return {
            pass: '',
            isInvalid: false,
        };
    },
    computed: {
        isValid() {
            return this.pass.trim().length >= 8 && this.pass.trim().length <= 50 && !this.isInvalid;
        },
    },
    methods: {
        validatePass() {
            // Validates that the room name is not empty, within the length limits, and contains only valid characters
            const regex = /^[a-zA-Z0-9-_]+$/; // Only alphanumeric, hyphen, and underscore
            this.isInvalid = !regex.test(this.pass.trim()) || this.pass.trim().length < 8 || this.pass.trim().length > 50;
        },
        navigateToRoom() {
            if (this.isValid) {
                this.$router.push({ path: '/whiteboard/' + this.pass });
            }
        },
    },
};
</script>

<style scoped>
.room-form {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    margin: 40px auto;
}

.title {
    font-size: 24px;
    font-weight: bold;
    color: #333;
    margin-bottom: 15px;
}

.label {
    font-size: 16px;
    color: #333;
    margin-bottom: 5px;
}

.input-field {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    border-radius: 4px;
    border: 2px solid #ccc;
    margin-bottom: 10px;
    outline: none;
    transition: border-color 0.3s;
}

.input-field:focus {
    border-color: #4CAF50;
}

.input-error {
    border-color: red !important;
}

.error-message {
    color: red;
    font-size: 12px;
    margin-bottom: 10px;
}

.enter-button {
    width: 100%;
    padding: 12px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-align: center;
    font-size: 16px;
    margin-top: 10px;
    transition: background-color 0.3s;
}

.enter-button:hover {
    background-color: #45a049;
}

button:disabled {
    background-color: #ccc;
    cursor: not-allowed;
}
</style>
