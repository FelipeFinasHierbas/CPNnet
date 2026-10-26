import React, { useEffect, useState } from "react";
import { AbsoluteFill, continueRender, delayRender, interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { TransitionSeries, linearTiming } from "@remotion/transitions";
import { slide } from "@remotion/transitions/slide";
import { fade } from "@remotion/transitions/fade";
import { AZURE, Backdrop, CYAN, Chip, FAMILY, Flags, Mascota, NAVY, Overlay, Particles, SKY, clampOpts, ease, fontsReady } from "./Reel";

export type WebinarProps = {
  readonly marca: string;
  readonly fondo: string;
  readonly dia: string;
  readonly mes: string;
  readonly gancho: readonly string[];
  readonly mensaje: readonly string[]; // línea 1 en blanco, línea 2 resaltada
  readonly titulo: string;
  readonly puntos: readonly string[];
  readonly horaChile: string;
  readonly horaPeruColombia: string;
  readonly seed: number;
};

const Words: React.FC<{ text: string; size: number; color?: string; delay: number; top: number; weight?: number }> = ({ text, size, color = "#fff", delay, top, weight = 800 }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  return (
    <div style={{ position: "absolute", left: 80, right: 80, top, display: "flex", flexWrap: "wrap", columnGap: size * 0.22, fontFamily: FAMILY, fontSize: size, fontWeight: weight, lineHeight: 1.06, letterSpacing: -2, color, textShadow: "0 6px 40px rgba(0,16,40,.85)" }}>
      {text.split(" ").map((w, i) => {
        const p = spring({ frame: frame - delay - i * 4, fps, config: { damping: 14, stiffness: 150 } });
        return (
          <span key={i} style={{ display: "inline-block", opacity: interpolate(p, [0, 0.6], [0, 1], clampOpts), translate: `0px ${interpolate(p, [0, 1], [70, 0])}px` }}>
            {w}
          </span>
        );
      })}
    </div>
  );
};

// ---- 1. gancho con el título del webinar (guía gráfica de Safetica)
const Gancho: React.FC<WebinarProps> = ({ marca, fondo, gancho, seed }) => {
  const frame = useCurrentFrame();
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.5} zoomFrom={1} />
      <Particles seed={seed} />
      <div style={{ position: "absolute", left: 80, top: 330, fontFamily: FAMILY, fontSize: 28, fontWeight: 700, letterSpacing: 8, color: CYAN, textTransform: "uppercase", opacity: interpolate(frame, [4, 20], [0, 1], clampOpts) }}>
        Webinar · Mes de la Ciberseguridad
      </div>
      <div style={{ position: "absolute", left: 80, top: 400, opacity: interpolate(frame, [8, 26], [0, 1], clampOpts), translate: `0px ${interpolate(frame, [8, 32], [40, 0], { ...clampOpts, easing: ease })}px` }}>
        <Chip marca={marca} height={96} />
      </div>
      <Words text={gancho[0]} size={132} delay={28} top={600} />
      <Words text={gancho[1]} size={78} color={CYAN} delay={50} top={920} />
      <Mascota left={620} top={1330} height={470} delay={70} from="bottom" />
    </AbsoluteFill>
  );
};

// ---- 2. mensaje
const Mensaje: React.FC<WebinarProps> = ({ mensaje, fondo, marca, seed }) => (
  <AbsoluteFill>
    <Backdrop fondo={fondo} dim={0.62} zoomFrom={1.08} />
    <Particles seed={seed + 3} />
    <Words text={mensaje[0]} size={104} delay={6} top={520} />
    <Words text={mensaje[1]} size={104} color={CYAN} delay={22} top={820} />
    <Mascota left={640} top={1180} height={560} delay={34} from="bottom" />
    <div style={{ position: "absolute", left: 80, top: 1560 }}><Chip marca={marca} height={74} /></div>
  </AbsoluteFill>
);

// ---- 3. patrones de riesgo
const Patrones: React.FC<WebinarProps> = ({ titulo, puntos, fondo, seed }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.66} zoomFrom={1.12} />
      <Particles seed={seed + 6} />
      <Words text={titulo} size={84} delay={4} top={400} />
      {puntos.map((t, i) => {
        const p = spring({ frame: frame - 28 - i * 16, fps, config: { damping: 15, stiffness: 130 } });
        return (
          <div key={i} style={{ position: "absolute", left: 80, right: 80, top: 760 + i * 190, height: 150, borderRadius: 40, background: "linear-gradient(135deg, rgba(32,60,113,.92), rgba(26,46,82,.92))", border: "2px solid rgba(132,186,225,.35)", boxShadow: "0 20px 50px rgba(0,10,30,.5)", display: "flex", alignItems: "center", gap: 34, padding: "0 44px", opacity: interpolate(p, [0, 0.5], [0, 1], clampOpts), translate: `${interpolate(p, [0, 1], [140, 0])}px 0px` }}>
            <div style={{ flex: "none", width: 84, height: 84, borderRadius: "50%", background: `linear-gradient(135deg, ${SKY}, ${AZURE})`, display: "flex", alignItems: "center", justifyContent: "center", fontFamily: FAMILY, fontSize: 42, fontWeight: 800, color: "#fff", boxShadow: "0 0 30px rgba(111,227,245,.5)" }}>{i + 1}</div>
            <div style={{ fontFamily: FAMILY, fontSize: 58, fontWeight: 700, color: "#fff", letterSpacing: -1 }}>{t}</div>
          </div>
        );
      })}
    </AbsoluteFill>
  );
};

// ---- 4. horario y llamado
const Cierre: React.FC<WebinarProps> = ({ marca, fondo, horaChile, horaPeruColombia, dia, mes, seed }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const pop = (d: number) => spring({ frame: frame - d, fps, config: { damping: 12 } });
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.58} zoomFrom={1.04} />
      <Particles seed={seed + 9} />
      <div style={{ position: "absolute", left: 80, top: 380, fontFamily: FAMILY, fontSize: 36, fontWeight: 700, letterSpacing: 8, color: CYAN, textTransform: "uppercase", opacity: interpolate(frame, [0, 14], [0, 1], clampOpts) }}>
        {dia} de {mes} · Webinar
      </div>
      <Words text={horaChile} size={118} delay={8} top={470} />
      <Words text={horaPeruColombia} size={64} color={SKY} delay={24} top={800} weight={700} />
      <div style={{ position: "absolute", left: 80, width: 560, top: 1010, height: 130, borderRadius: 65, background: "linear-gradient(135deg,#2a5aa8,#3f86d0)", display: "flex", alignItems: "center", justifyContent: "center", fontFamily: FAMILY, fontSize: 56, fontWeight: 800, color: "#fff", boxShadow: `0 0 ${40 + Math.sin(frame / 5) * 25}px rgba(111,227,245,.7)`, scale: pop(34) * (1 + Math.sin(frame / 6) * 0.015), transformOrigin: "left center" }}>
        Regístrate
      </div>
      <div style={{ position: "absolute", left: 80, top: 1200, opacity: interpolate(frame, [44, 58], [0, 1], clampOpts) }}><Chip marca={marca} height={80} /></div>
      <div style={{ position: "absolute", left: 80, top: 1360, display: "flex" }}><Flags size={68} delay={52} /></div>
      <div style={{ position: "absolute", left: 80, top: 1480, fontFamily: FAMILY, fontSize: 38, fontWeight: 700, color: "#fff", opacity: interpolate(frame, [60, 74], [0, 1], clampOpts) }}>#webinarscpnnet</div>
      <Mascota left={560} top={1090} height={680} delay={40} />
    </AbsoluteFill>
  );
};

export const Webinar: React.FC<WebinarProps> = (props) => {
  const [handle] = useState(() => delayRender("Cargando fuentes"));
  useEffect(() => {
    fontsReady.then(() => continueRender(handle));
  }, [handle]);
  const { durationInFrames } = useVideoConfig();
  const T = 15;
  return (
    <AbsoluteFill style={{ backgroundColor: NAVY, fontFamily: FAMILY }}>
      <TransitionSeries>
        <TransitionSeries.Sequence name="Gancho" durationInFrames={150}><Gancho {...props} /></TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="Mensaje" durationInFrames={150}><Mensaje {...props} /></TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={slide({ direction: "from-right" })} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="Patrones de riesgo" durationInFrames={150}><Patrones {...props} /></TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={slide({ direction: "from-bottom" })} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="Cierre" durationInFrames={150}><Cierre {...props} /></TransitionSeries.Sequence>
      </TransitionSeries>
      <Overlay total={durationInFrames} />
    </AbsoluteFill>
  );
};
