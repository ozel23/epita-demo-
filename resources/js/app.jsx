import React from 'react';
import {createRoot} from 'react-dom/client';
import {createInertiaApp} from '@inertiajs/react';
import {pages} from './Pages/index';
import '../css/inertia.css';
createInertiaApp({title:t=>`${t} · EPITA`,resolve:name=>{if(!pages[name])throw new Error(`Halaman tidak tersedia: ${name}`);return pages[name]();},setup({el,App,props}){createRoot(el).render(<App {...props}/>);},progress:{color:'#e4bc35'}});
