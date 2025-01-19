<template>
    <div>
        <label for="pass">Enter the room name:</label>
        <br/>
        <input type="text" name="pass" v-model="pass">
        <router-link :to="{ path: '/whiteboard/'+ this.pass}" append>Enter</router-link>
    </div>
</template>

<script>
export default {
    data() {
        return {
            pass: '',
        }
    },
}
</script>
