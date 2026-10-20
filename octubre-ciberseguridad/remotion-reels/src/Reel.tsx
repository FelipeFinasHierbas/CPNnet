import React, { useEffect, useState } from "react";
import {
  AbsoluteFill,
  Easing,
  Img,
  continueRender,
  delayRender,
  interpolate,
  spring,
  staticFile,
  useCurrentFrame,
  useVideoConfig,
} from "remotion";
import { loadFont } from "@remotion/fonts";
import { TransitionSeries, linearTiming } from "@remotion/transitions";
import { slide } from "@remotion/transitions/slide";
import { fade } from "@remotion/transitions/fade";

const NAVY = "#1A2E52";
const ROYAL = "#203C71";
const AZURE = "#3A84BD";
const SKY = "#84BAE1";
const CYAN = "#6fe3f5";
const FAMILY = "Montserrat";

const fontsReady = Promise.all(
  ["400", "500", "600", "700", "800"].map((w) =>
    loadFont({ family: FAMILY, url: staticFile(`fonts/Montserrat-${w}.ttf`), weight: w }),
  ),
);

export type ReelProps = {
  readonly pilar: string;
  readonly marca: string;
  readonly fondo: string;
  readonly pregunta: string; // las palabras entre *asteriscos* van resaltadas
  readonly puntos: readonly string[];
  readonly seed: number;
};

const ease = Easing.bezier(0.16, 1, 0.3, 1);
const clampOpts = { extrapolateLeft: "clamp", extrapolateRight: "clamp" } as const;

// ------------------------------------------------------------------ piezas reutilizables
const Backdrop: React.FC<{ fondo: string; dim: number; zoomFrom: number }> = ({ fondo, dim, zoomFrom }) => {
  const frame = useCurrentFrame();
  const { durationInFrames } = useVideoConfig();
  return (
    <AbsoluteFill style={{ backgroundColor: NAVY, overflow: "hidden" }}>
      <Img
        src={staticFile(fondo)}
        style={{
          position: "absolute",
          inset: 0,
          width: "100%",
          height: "100%",
          objectFit: "cover",
          scale: interpolate(frame, [0, durationInFrames], [zoomFrom, zoomFrom + 0.12], clampOpts),
          translate: interpolate(frame, [0, durationInFrames], ["0px 0px", "-30px -40px"], clampOpts),
        }}
      />
      <AbsoluteFill style={{ backgroundColor: `rgba(6,22,48,${dim})` }} />
    </AbsoluteFill>
  );
};

const Particles: React.FC<{ seed: number }> = ({ seed }) => {
  const frame = useCurrentFrame();
  const dots = Array.from({ length: 22 }, (_, i) => {
    const r = (n: number) => ((Math.sin(seed * 97 + i * 131 + n * 17) + 1) / 2);
    return { x: r(1) * 1080, y: r(2) * 1920, s: 6 + r(3) * 16, v: 0.4 + r(4) * 1.4, o: 0.25 + r(5) * 0.45 };
  });
  return (
    <AbsoluteFill>
      {dots.map((d, i) => (
        <div
          key={i}
          style={{
            position: "absolute",
            left: d.x,
            top: (((d.y - frame * d.v * 3) % 1960) + 1960) % 1960 - 40,
            width: d.s,
            height: d.s,
            borderRadius: "50%",
            backgroundColor: i % 3 === 0 ? CYAN : "#ffd27a",
            opacity: d.o,
            filter: "blur(1.5px)",
          }}
        />
      ))}
    </AbsoluteFill>
  );
};

const Chip: React.FC<{ marca: string; height: number; style?: React.CSSProperties }> = ({ marca, height, style }) => (
  <div
    style={{
      display: "flex",
      alignItems: "center",
      justifyContent: "center",
      padding: `${height * 0.28}px ${height * 0.5}px`,
      borderRadius: height * 0.5,
      background: `linear-gradient(135deg, ${ROYAL}, ${NAVY})`,
      boxShadow: "0 18px 48px rgba(0,10,30,.45)",
      ...style,
    }}
  >
    <Img src={staticFile(`logos/${marca}.png`)} style={{ height, objectFit: "contain" }} />
  </div>
);

const Flags: React.FC<{ size: number; delay?: number }> = ({ size, delay = 0 }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  return (
    <div style={{ display: "flex", gap: size * 0.28 }}>
      {["cl", "pe", "us", "co"].map((c, i) => (
        <Img
          key={c}
          src={staticFile(`flags/${c}.svg`)}
          style={{
            width: size,
            height: size,
            scale: spring({ frame: frame - delay - i * 4, fps, config: { damping: 11 } }),
          }}
        />
      ))}
    </div>
  );
};

const Mascota: React.FC<{ left: number; top: number; height: number; delay?: number; from?: "right" | "bottom" }> = ({ left, top, height, delay = 0, from = "right" }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const p = spring({ frame: frame - delay, fps, config: { damping: 12, stiffness: 110 } });
  const bob = Math.sin((frame - delay) / 14) * 10;
  const off = from === "right" ? `${(1 - p) * 500}px ${bob}px` : `0px ${(1 - p) * 500 + bob}px`;
  return (
    <Img
      src={staticFile("mascota.png")}
      style={{
        position: "absolute",
        left,
        top,
        height,
        translate: off,
        rotate: `${interpolate(p, [0, 1], [8, 0])}deg`,
        filter: "drop-shadow(0 24px 40px rgba(0,10,30,.6))",
      }}
    />
  );
};

// ------------------------------------------------------------------ escena 1: gancho
const Hook: React.FC<ReelProps> = ({ pilar, marca, fondo, pregunta, seed }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  let on = false;
  const words = pregunta.split(" ").map((w) => {
    const start = w.startsWith("*");
    if (start) on = true;
    const item = { w: w.replace(/\*/g, ""), hl: on };
    if (w.endsWith("*")) on = false;
    return item;
  });
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.18} zoomFrom={1} />
      <Particles seed={seed} />
      <div
        style={{
          position: "absolute",
          left: 80,
          top: 520,
          fontFamily: FAMILY,
          fontSize: 26,
          fontWeight: 700,
          letterSpacing: 4,
          color: CYAN,
          textTransform: "uppercase",
          opacity: interpolate(frame, [4, 20], [0, 1], clampOpts),
          translate: interpolate(frame, [4, 24], ["-60px 0px", "0px 0px"], { ...clampOpts, easing: ease }),
        }}
      >
        Mes de la Ciberseguridad · {pilar}
      </div>
      <div
        style={{
          position: "absolute",
          left: 80,
          right: 80,
          top: 600,
          display: "flex",
          flexWrap: "wrap",
          columnGap: 24,
          fontFamily: FAMILY,
          fontSize: 118,
          lineHeight: 1.04,
          fontWeight: 800,
          letterSpacing: -3,
          color: "#fff",
          textShadow: "0 6px 40px rgba(0,16,40,.9)",
        }}
      >
        {words.map(({ w: clean, hl }, i) => {
          const p = spring({ frame: frame - 12 - i * 5, fps, config: { damping: 13, stiffness: 140 } });
          return (
            <span
              key={i}
              style={{
                display: "inline-block",
                color: hl ? CYAN : "#fff",
                opacity: interpolate(p, [0, 0.6], [0, 1], clampOpts),
                translate: `0px ${interpolate(p, [0, 1], [90, 0])}px`,
                scale: interpolate(p, [0, 1], [0.8, 1]),
              }}
            >
              {clean}
            </span>
          );
        })}
      </div>
      <Mascota left={600} top={1010} height={640} delay={50} />
      <Chip
        marca={marca}
        height={96}
        style={{
          position: "absolute",
          left: 80,
          top: 1230,
          scale: spring({ frame: frame - 62, fps, config: { damping: 12 } }),
          transformOrigin: "left center",
        }}
      />
    </AbsoluteFill>
  );
};

// ------------------------------------------------------------------ escenas 2-4: un punto por escena
const Point: React.FC<{ n: number; text: string } & ReelProps> = ({ n, text, marca, fondo, seed, pilar }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const words = text.split(" ");
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.62} zoomFrom={1.08 + n * 0.02} />
      <Particles seed={seed + n} />
      <div
        style={{
          position: "absolute",
          left: 60,
          top: 380,
          fontFamily: FAMILY,
          fontSize: 560,
          fontWeight: 800,
          lineHeight: 1,
          color: "transparent",
          WebkitTextStroke: `6px ${SKY}`,
          opacity: interpolate(frame, [0, 18], [0, 0.55], clampOpts),
          translate: interpolate(frame, [0, 30], ["-200px 0px", "0px 0px"], { ...clampOpts, easing: ease }),
        }}
      >
        {n}
      </div>
      <div
        style={{
          position: "absolute",
          left: 80,
          right: 80,
          top: 880,
          display: "flex",
          flexWrap: "wrap",
          columnGap: 22,
          fontFamily: FAMILY,
          fontSize: 78,
          lineHeight: 1.12,
          fontWeight: 800,
          color: "#fff",
          letterSpacing: -1,
          textShadow: "0 4px 30px rgba(0,10,30,.9)",
        }}
      >
        {words.map((w, i) => {
          const p = spring({ frame: frame - 10 - i * 3, fps, config: { damping: 14, stiffness: 150 } });
          return (
            <span
              key={i}
              style={{
                display: "inline-block",
                opacity: interpolate(p, [0, 0.7], [0, 1], clampOpts),
                translate: `0px ${interpolate(p, [0, 1], [60, 0])}px`,
              }}
            >
              {w}
            </span>
          );
        })}
      </div>
      <div
        style={{
          position: "absolute",
          left: 80,
          top: 1500,
          opacity: interpolate(frame, [30, 45], [0, 1], clampOpts),
        }}
      >
        <Chip marca={marca} height={70} />
      </div>
      <Mascota left={700} top={1330} height={400} delay={20} from="bottom" />
    </AbsoluteFill>
  );
};

// ------------------------------------------------------------------ escena final: llamado
const Cta: React.FC<ReelProps> = ({ marca, fondo, seed }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const pop = (d: number) => spring({ frame: frame - d, fps, config: { damping: 12 } });
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.5} zoomFrom={1.05} />
      <Particles seed={seed + 9} />
      <Img
        src={staticFile("logo-blanco.png")}
        style={{ position: "absolute", left: 320, top: 290, width: 440, scale: pop(0) }}
      />
      <div
        style={{
          position: "absolute",
          left: 80,
          right: 80,
          top: 600,
          textAlign: "center",
          fontFamily: FAMILY,
          fontSize: 92,
          lineHeight: 1.08,
          fontWeight: 800,
          color: "#fff",
          letterSpacing: -2,
          opacity: interpolate(frame, [12, 28], [0, 1], clampOpts),
          translate: `0px ${interpolate(frame, [12, 36], [70, 0], { ...clampOpts, easing: ease })}px`,
        }}
      >
        Habla con tu ejecutivo <span style={{ color: CYAN }}>CPNnet</span>
      </div>
      <div
        style={{
          position: "absolute",
          left: 130,
          right: 130,
          top: 930,
          height: 130,
          borderRadius: 65,
          background: `linear-gradient(135deg, #2a5aa8, #3f86d0)`,
          boxShadow: `0 0 ${40 + Math.sin(frame / 5) * 25}px rgba(111,227,245,.75)`,
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          fontFamily: FAMILY,
          fontSize: 50,
          fontWeight: 800,
          color: "#fff",
          scale: pop(26) * (1 + Math.sin(frame / 6) * 0.015),
        }}
      >
        #CPNnetPartners
      </div>
      <div style={{ position: "absolute", left: 80, top: 1330, display: "flex", opacity: interpolate(frame, [36, 50], [0, 1], clampOpts) }}>
        <Chip marca={marca} height={78} />
      </div>
      <Mascota left={600} top={1130} height={620} delay={30} />
      <div style={{ position: "absolute", left: 80, top: 1480, display: "flex" }}>
        <Flags size={70} delay={40} />
      </div>
    </AbsoluteFill>
  );
};

// ------------------------------------------------------------------ capa fija: marca + arco + progreso
const Overlay: React.FC<{ total: number }> = ({ total }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  return (
    <AbsoluteFill style={{ pointerEvents: "none" }}>
      <svg width={1080} height={1920} viewBox="0 0 1080 1920" style={{ position: "absolute", inset: 0 }}>
        <defs>
          <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stopColor={CYAN} />
            <stop offset="1" stopColor={AZURE} />
          </linearGradient>
        </defs>
        <g transform={`rotate(${frame * 1.6} 930 250)`}>
          <circle cx={930} cy={250} r={120} fill="none" stroke="url(#g)" strokeWidth={14} strokeLinecap="round" strokeDasharray="520 900" opacity={0.85} />
        </g>
        <g transform={`rotate(${-frame * 2.4} 930 250)`}>
          <circle cx={930} cy={250} r={86} fill="none" stroke={SKY} strokeWidth={6} strokeLinecap="round" strokeDasharray="160 500" opacity={0.7} />
        </g>
      </svg>
      <Img
        src={staticFile("logo-blanco.png")}
        style={{ position: "absolute", left: 80, top: 130, width: 250, opacity: interpolate(frame, [0, 15, 405, 420], [0, 1, 1, 0], clampOpts), translate: interpolate(frame, [0, 20], ["0px -30px", "0px 0px"], { ...clampOpts, easing: ease }) }}
      />
      <div style={{ position: "absolute", left: 80, right: 80, bottom: 90, height: 10, borderRadius: 5, backgroundColor: "rgba(255,255,255,.18)" }}>
        <div style={{ width: `${interpolate(frame, [0, total - 1 * fps], [0, 100], clampOpts)}%`, height: "100%", borderRadius: 5, background: `linear-gradient(90deg, ${SKY}, ${CYAN})` }} />
      </div>
    </AbsoluteFill>
  );
};

// ------------------------------------------------------------------ reel completo
export const Reel: React.FC<ReelProps> = (props) => {
  const [handle] = useState(() => delayRender("Cargando fuentes"));
  useEffect(() => {
    fontsReady.then(() => continueRender(handle));
  }, [handle]);
  const { durationInFrames } = useVideoConfig();
  const T = 15;
  return (
    <AbsoluteFill style={{ backgroundColor: NAVY, fontFamily: FAMILY }}>
      <TransitionSeries>
        <TransitionSeries.Sequence name="Gancho" durationInFrames={120}>
          <Hook {...props} />
        </TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={slide({ direction: "from-right" })} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="Punto 1" durationInFrames={120}>
          <Point n={1} text={props.puntos[0]} {...props} />
        </TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={slide({ direction: "from-bottom" })} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="Punto 2" durationInFrames={120}>
          <Point n={2} text={props.puntos[1]} {...props} />
        </TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={slide({ direction: "from-right" })} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="Punto 3" durationInFrames={120}>
          <Point n={3} text={props.puntos[2]} {...props} />
        </TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="Llamado" durationInFrames={120}>
          <Cta {...props} />
        </TransitionSeries.Sequence>
      </TransitionSeries>
      <Overlay total={durationInFrames} />
    </AbsoluteFill>
  );
};
