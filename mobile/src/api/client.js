import * as Crypto from 'expo-crypto';
import {Platform} from 'react-native';
import * as SecureStore from 'expo-secure-store';
const BASE=(process.env.EXPO_PUBLIC_API_URL||(__DEV__?(Platform.OS==='android'?'http://10.0.2.2:8080/api/v1':'http://127.0.0.1:8080/api/v1'):'https://epita.example.com/api/v1')).replace(/\/$/,'');
if(!BASE.startsWith('https://')&&!__DEV__)throw new Error('Production API wajib HTTPS');
const TOKEN='epita.token';
export const tokenStore={get:()=>SecureStore.getItemAsync(TOKEN),set:v=>SecureStore.setItemAsync(TOKEN,v),clear:()=>SecureStore.deleteItemAsync(TOKEN)};
export async function api(path,{method='GET',body,key}={}){const token=await tokenStore.get();const controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),30000);try{const multipart=body instanceof FormData;const response=await fetch(BASE+path,{method,headers:{Accept:'application/json',...(token?{Authorization:`Bearer ${token}`}:{ }),...(!multipart&&body?{'Content-Type':'application/json'}:{}),...(key?{'Idempotency-Key':key}:{})},body:body?(multipart?body:JSON.stringify(body)):undefined,signal:controller.signal});const payload=await response.json();if(!response.ok){const error=new Error(payload.message||'Permintaan gagal.');error.status=response.status;error.errors=payload.errors;throw error;}return payload;}finally{clearTimeout(timeout)}}

export async function deviceId(){let value=await SecureStore.getItemAsync('epita.device-id');if(!value){value=Crypto.randomUUID();await SecureStore.setItemAsync('epita.device-id',value)}return value}
