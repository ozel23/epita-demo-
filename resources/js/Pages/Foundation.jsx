import React from 'react';
import {Head} from '@inertiajs/react';
import Workspace from '../Layouts/Workspace';
export default function Foundation({plants}) {
 return <Workspace><Head title="Workspace React"/><p className="eyebrow">MIGRASI BERTAHAP</p><h1>Workspace EPITA</h1><p>React Inertia berjalan melalui Laravel. Modul operasional tersedia melalui dashboard selama konversi halaman berlangsung.</p><section aria-labelledby="plants-title"><h2 id="plants-title">Plant yang diizinkan</h2>{plants.length ? <ul>{plants.map(plant=><li key={plant.id}><strong>{plant.code}</strong> — {plant.name}</li>)}</ul> : <p>Belum ada plant yang diberikan.</p>}</section><a className="button" href="/dashboard">Buka dashboard operasional</a></Workspace>;
}
