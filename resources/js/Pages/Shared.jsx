import React from 'react';
import {Head} from '@inertiajs/react';
import Workspace from '../Layouts/Workspace';
export const list=v=>v?.data||v||[];
export const pick=(name,options,extra={})=>({name,options,...extra});
export const field=(name,type='text',extra={})=>({name,type,...extra});
export function Page({title,children}){return <Workspace title={title}><Head title={title}/><h1>{title}</h1>{children}</Workspace>}
